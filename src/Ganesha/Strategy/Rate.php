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
     * @var array
     */
    private static $requirements = [
        'adapter',
        'failureRate',
        'intervalToHalfOpen',
        'minimumRequests',
        'timeWindow',
    ];

    /**
     * @param Configuration $configuration
     */
    private function __construct(Configuration $configuration, Storage $storage)
    {
        $this->configuration = $configuration;
        $this->storage = $storage;
    }

    /**
     * @param array $params
     * @throws \LogicException
     */
    public static function validate($params)
    {
        foreach (self::$requirements as $r) {
            if (!isset($params[$r])) {
                throw new \LogicException($r . ' is required');
            }
        }
    }

    /**
     * @param Configuration $configuration
     * @return Rate
     */
    public static function create(Configuration $configuration)
    {
        $strategy = new self(
            $configuration,
            new Storage(
                $configuration['adapter'],
                self::resourceDecorator($configuration['timeWindow'])
            )
        );

        return $strategy;
    }

    /**
     * @param  string $resource
     * @return int
     */
    public function recordFailure($resource)
    {
        $this->storage->setLastFailureTime($resource, time());
        $this->storage->incrementFailureCount($resource);
        if (
            $this->storage->getStatus($resource) === Ganesha::STATUS_CALMED_DOWN
            && $this->isClosedInCurrentTimeWindow($resource) === false
        ) {
            $this->storage->setStatus($resource, Ganesha::STATUS_TRIPPED);
            return Ganesha::STATUS_TRIPPED;
        }

        return Ganesha::STATUS_CALMED_DOWN;
    }

    /**
     * @param  string $resource
     * @return null | int
     */
    public function recordSuccess($resource)
    {
        $this->storage->incrementSuccessCount($resource);
        if (
            $this->storage->getStatus($resource) === Ganesha::STATUS_TRIPPED
            && $this->isClosedInPreviousTimeWindow($resource)
        ) {
            $this->storage->setStatus($resource, Ganesha::STATUS_CALMED_DOWN);
            return Ganesha::STATUS_CALMED_DOWN;
        }
    }

    /**
     * @return void
     */
    public function reset()
    {
        $this->storage->reset();
    }

    /**
     * @param  string $resource
     * @return bool
     */
    public function isAvailable($resource)
    {
        if ($this->isClosed($resource) || $this->isHalfOpen($resource)) {
            return true;
        }

        $this->storage->incrementRejectionCount($resource);
        return false;
    }

    /**
     * @return bool
     * @throws StorageException
     */
    private function isClosed($resource)
    {
        return $this->isClosedInCurrentTimeWindow($resource) && $this->isClosedInPreviousTimeWindow($resource);
    }

    /**
     * @param  string $resource
     * @return bool
     */
    private function isClosedInCurrentTimeWindow($resource)
    {
        $failure = $this->storage->getFailureCount($resource);
        if (
            $failure === 0
            || ($failure / $this->configuration['minimumRequests']) * 100 < $this->configuration['failureRate']
        ) {
            return true;
        }

        $success = $this->storage->getSuccessCount($resource);
        $rejection = $this->storage->getRejectionCount($resource);

        return $this->isClosedInTimeWindow($failure, $success, $rejection);
    }

    /**
     * @param  string $resource
     * @return bool
     */
    private function isClosedInPreviousTimeWindow($resource)
    {
        $failure = $this->storage->getFailureCountByCustomKey(self::keyForPreviousTimeWindow($resource, $this->configuration['timeWindow']));
        if (
            $failure === 0
            || ($failure / $this->configuration['minimumRequests']) * 100 < $this->configuration['failureRate']
        ) {
            return true;
        }

        $success = $this->storage->getSuccessCountByCustomKey(self::keyForPreviousTimeWindow($resource, $this->configuration['timeWindow']));
        $rejection = $this->storage->getRejectionCountByCustomKey(self::keyForPreviousTimeWindow($resource, $this->configuration['timeWindow']));

        return $this->isClosedInTimeWindow($failure, $success, $rejection);
    }

    /**
     * @param  int $failure
     * @param  int $success
     * @param  int $rejection
     * @return bool
     */
    private function isClosedInTimeWindow($failure, $success, $rejection)
    {
        if (($failure + $success + $rejection) < $this->configuration['minimumRequests']) {
            return true;
        }

        if (($failure / ($failure + $success)) * 100 < $this->configuration['failureRate']) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     * @throws StorageException
     */
    private function isHalfOpen($resource)
    {
        if (is_null($lastFailureTime = $this->storage->getLastFailureTime($resource))) {
            return false;
        }

        if ((time() - $lastFailureTime) > $this->configuration['intervalToHalfOpen']) {
            $this->storage->setLastFailureTime($resource, time());
            return true;
        }

        return false;
    }

    private static function resourceDecorator($timeWindow, $current = true)
    {
        return function ($resource) use ($timeWindow, $current) {
            return sprintf(
                '%s.%d',
                $resource,
                $current ? (int)floor(time() / $timeWindow) : (int)floor((time() - $timeWindow) / $timeWindow)
            );
        };
    }

    private static function keyForPreviousTimeWindow($resource, $timeWindow)
    {
        $f = self::resourceDecorator($timeWindow, false);
        return $f($resource);
    }
}
