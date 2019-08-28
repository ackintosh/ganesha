<?php
namespace Ackintosh\Ganesha\Strategy;

use Ackintosh\Ganesha;
use Ackintosh\Ganesha\Configuration;
use Ackintosh\Ganesha\Exception\StorageException;
use Ackintosh\Ganesha\Storage;
use Ackintosh\Ganesha\StrategyInterface;
use Ackintosh\Ganesha\Storage\StorageKeysInterface;

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
        'failureRateThreshold',
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

        if (!call_user_func([$params['adapter'], 'supportRateStrategy'])) {
            throw new \InvalidArgumentException(get_class($params['adapter']) . " doesn't support Rate Strategy.");
        }
    }

    /**
     * @param Configuration $configuration
     * @return Rate
     */
    public static function create(Configuration $configuration, StorageKeysInterface $keys = null)
    {
        $serviceNameDecorator = $configuration['adapter'] instanceof Storage\Adapter\TumblingTimeWindowInterface ? self::serviceNameDecorator($configuration['timeWindow']) : null;
        $adapter = $configuration['adapter'];
        $adapter->setConfiguration($configuration);

        return new self(
            $configuration,
            new Storage($adapter, $serviceNameDecorator, $keys)
        );
    }

    /**
     * @param  string $service
     * @return int
     */
    public function recordFailure($service)
    {
        $this->storage->setLastFailureTime($service, time());
        $this->storage->incrementFailureCount($service);
        if (
            $this->storage->getStatus($service) === Ganesha::STATUS_CALMED_DOWN
            && $this->isClosedInCurrentTimeWindow($service) === false
        ) {
            $this->storage->setStatus($service, Ganesha::STATUS_TRIPPED);
            return Ganesha::STATUS_TRIPPED;
        }

        return Ganesha::STATUS_CALMED_DOWN;
    }

    /**
     * @param  string $service
     * @return null | int
     */
    public function recordSuccess($service)
    {
        $this->storage->incrementSuccessCount($service);
        if (
            $this->storage->getStatus($service) === Ganesha::STATUS_TRIPPED
            && $this->isClosedInPreviousTimeWindow($service)
        ) {
            $this->storage->setStatus($service, Ganesha::STATUS_CALMED_DOWN);
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
     * @param  string $service
     * @return bool
     */
    public function isAvailable($service)
    {
        if ($this->isClosed($service) || $this->isHalfOpen($service)) {
            return true;
        }

        $this->storage->incrementRejectionCount($service);
        return false;
    }

    /**
     * @return bool
     * @throws StorageException, \LogicException
     */
    private function isClosed($service)
    {
        switch (true) {
            case $this->storage->supportSlidingTimeWindow():
                return $this->isClosedInCurrentTimeWindow($service);
                break;
            case $this->storage->supportTumblingTimeWindow():
                return $this->isClosedInCurrentTimeWindow($service) && $this->isClosedInPreviousTimeWindow($service);
                break;
            default:
                throw new \LogicException(sprintf(
                    'storage adapter should implement %s and/or %s.',
                    Storage\Adapter\SlidingTimeWindowInterface::class,
                    Storage\Adapter\TumblingTimeWindowInterface::class
                ));
                break;
        }
    }

    /**
     * @param  string $service
     * @return bool
     */
    private function isClosedInCurrentTimeWindow($service)
    {
        $failure = $this->storage->getFailureCount($service);
        if (
            $failure === 0
            || ($failure / $this->configuration['minimumRequests']) * 100 < $this->configuration['failureRateThreshold']
        ) {
            return true;
        }

        $success = $this->storage->getSuccessCount($service);
        $rejection = $this->storage->getRejectionCount($service);

        return $this->isClosedInTimeWindow($failure, $success, $rejection);
    }

    /**
     * @param  string $service
     * @return bool
     */
    private function isClosedInPreviousTimeWindow($service)
    {
        $failure = $this->storage->getFailureCountByCustomKey(self::keyForPreviousTimeWindow($service, $this->configuration['timeWindow']));
        if (
            $failure === 0
            || ($failure / $this->configuration['minimumRequests']) * 100 < $this->configuration['failureRateThreshold']
        ) {
            return true;
        }

        $success = $this->storage->getSuccessCountByCustomKey(self::keyForPreviousTimeWindow($service, $this->configuration['timeWindow']));
        $rejection = $this->storage->getRejectionCountByCustomKey(self::keyForPreviousTimeWindow($service, $this->configuration['timeWindow']));

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

        if (($failure / ($failure + $success)) * 100 < $this->configuration['failureRateThreshold']) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     * @throws StorageException
     */
    private function isHalfOpen($service)
    {
        if (is_null($lastFailureTime = $this->storage->getLastFailureTime($service))) {
            return false;
        }

        if ((time() - $lastFailureTime) > $this->configuration['intervalToHalfOpen']) {
            $this->storage->setLastFailureTime($service, time());
            return true;
        }

        return false;
    }

    private static function serviceNameDecorator($timeWindow, $current = true)
    {
        return function ($service) use ($timeWindow, $current) {
            return sprintf(
                '%s.%d',
                $service,
                $current ? (int)floor(time() / $timeWindow) : (int)floor((time() - $timeWindow) / $timeWindow)
            );
        };
    }

    private static function keyForPreviousTimeWindow($service, $timeWindow)
    {
        $f = self::serviceNameDecorator($timeWindow, false);
        return $f($service);
    }
}
