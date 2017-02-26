<?php
namespace Ackintosh\Ganesha\Strategy;

use Ackintosh\Ganesha;
use Ackintosh\Ganesha\Configuration;
use Ackintosh\Ganesha\Exception\StorageException;
use Ackintosh\Ganesha\Storage;
use Ackintosh\Ganesha\StrategyInterface;

class Rate implements StrategyInterface
{
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var \Ackintosh\Ganesha\Storage
     */
    private $storage;

    /**
     * @param Configuration $configuration
     * @return Rate
     */
    public static function create(Configuration $configuration)
    {
        $strategy = new self();
        $strategy->setConfiguration($configuration);
        $strategy->setStorage(
            new Storage(
                call_user_func($configuration->getAdapterSetupFunction()),
                $configuration['countTTL'],
                self::serviceNameDecorator($configuration['timeWindow'])
            )
        );

        return $strategy;
    }

    /**
     * @param  Configuration $configuration
     * @return void
     */
    public function setConfiguration(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @param \Ackintosh\Ganesha\Storage $storage
     * @return void
     */
    public function setStorage($storage)
    {
        $this->storage = $storage;
    }

    /**
     * @param  string $serviceName
     * @return int
     */
    public function recordFailure($serviceName)
    {
        $this->storage->setLastFailureTime($serviceName, time());
        $this->storage->incrementFailureCount($serviceName);
        if (
            $this->storage->getStatus($serviceName) === Ganesha::STATUS_CALMED_DOWN
            && $this->isClosedInCurrentTimeWindow($serviceName) === false
        ) {
            $this->storage->setStatus($serviceName, Ganesha::STATUS_TRIPPED);
            return Ganesha::STATUS_TRIPPED;
        }

        return Ganesha::STATUS_CALMED_DOWN;
    }

    /**
     * @param  string $serviceName
     * @return void
     */
    public function recordSuccess($serviceName)
    {
        $this->storage->incrementSuccessCount($serviceName);
        if (
            $this->storage->getFailureCount($serviceName) === 0
            && $this->storage->getStatus($serviceName) === Ganesha::STATUS_TRIPPED
        ) {
            $this->storage->setStatus($serviceName, Ganesha::STATUS_CALMED_DOWN);
        }
    }

    /**
     * @param  string $serviceName
     * @return bool
     */
    public function isAvailable($serviceName)
    {
        if ($this->isClosed($serviceName) || $this->isHalfOpen($serviceName)) {
            return true;
        }

        $this->storage->incrementRejectionCount($serviceName);
        return false;
    }

    /**
     * @return bool
     * @throws StorageException
     */
    private function isClosed($serviceName)
    {
        if (
            $this->isClosedInPreviousTimeWindow($serviceName)
            && $this->isClosedInCurrentTimeWindow($serviceName)
        ) {
            return true;
        }

        return false;
    }

    private function isClosedInPreviousTimeWindow($serviceName)
    {
        $failure = $this->storage->getFailureCountByCustomKey(self::keyForPreviousTimeWindow($serviceName, $this->configuration['timeWindow']));
        $success = $this->storage->getSuccessCountByCustomKey(self::keyForPreviousTimeWindow($serviceName, $this->configuration['timeWindow']));
        $rejection = $this->storage->getRejectionCountByCustomKey(self::keyForPreviousTimeWindow($serviceName, $this->configuration['timeWindow']));

        $total = $failure + $success + $rejection;
        if ($total < $this->configuration['minimumRequests']) {
            return true;
        }

        if (($failure / $total) * 100 < $this->configuration['failureRate']) {
            return true;
        }

        return false;
    }

    private function isClosedInCurrentTimeWindow($serviceName)
    {
        $failure = $this->storage->getFailureCount($serviceName);
        $success = $this->storage->getSuccessCount($serviceName);
        $rejection = $this->storage->getRejectionCount($serviceName);

        $total = $failure + $success + $rejection;
        if ($total < $this->configuration['minimumRequests']) {
            return true;
        }

        if (($failure / $total) * 100 < $this->configuration['failureRate']) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     * @throws StorageException
     */
    private function isHalfOpen($serviceName)
    {
        if (is_null($lastFailureTime = $this->storage->getLastFailureTime($serviceName))) {
            return false;
        }

        if ((time() - $lastFailureTime) > $this->configuration['intervalToHalfOpen']) {
            $this->storage->setLastFailureTime($serviceName, time());
            return true;
        }

        return false;
    }

    private static function serviceNameDecorator($timeWindow, $current = true)
    {
        return function ($serviceName) use ($timeWindow, $current) {
            return sprintf(
                '%s.%d',
                $serviceName,
                $current ? (int)floor(time() / $timeWindow) : (int)floor(time() - $timeWindow / $timeWindow)
            );
        };
    }

    private static function keyForPreviousTimeWindow($serviceName, $timeWindow)
    {
        $f = self::serviceNameDecorator($timeWindow, false);
        return $f($serviceName);
    }
}
