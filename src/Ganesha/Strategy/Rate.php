<?php
namespace Ackintosh\Ganesha\Strategy;

use Ackintosh\Ganesha;
use Ackintosh\Ganesha\Configuration;
use Ackintosh\Ganesha\Exception\StorageException;
use Ackintosh\Ganesha\Storage;
use Ackintosh\Ganesha\StrategyInterface;
use InvalidArgumentException;
use LogicException;

class Rate implements StrategyInterface
{
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var Storage
     */
    private $storage;

    /**
     * @var array
     */
    private static $requirements = [
        Configuration::ADAPTER,
        Configuration::FAILURE_RATE_THRESHOLD,
        Configuration::INTERVAL_TO_HALF_OPEN,
        Configuration::MINIMUM_REQUESTS,
        Configuration::TIME_WINDOW,
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
     * @throws LogicException
     */
    public static function validate(array $params): void
    {
        foreach (self::$requirements as $r) {
            if (!isset($params[$r])) {
                throw new LogicException($r . ' is required');
            }
        }

        if (!call_user_func([$params['adapter'], 'supportRateStrategy'])) {
            throw new InvalidArgumentException(get_class($params['adapter']) . " doesn't support Rate Strategy.");
        }
    }

    /**
     * @param Storage\AdapterInterface $adapter
     * @param Configuration $configuration
     * @return Rate
     */
    public static function create(Storage\AdapterInterface $adapter, Configuration $configuration): StrategyInterface
    {
        $serviceNameDecorator = $adapter instanceof Storage\Adapter\TumblingTimeWindowInterface ? self::serviceNameDecorator($configuration->timeWindow()) : null;

        return new self(
            $configuration,
            new Storage(
                $adapter,
                $configuration->storageKeys(),
                $serviceNameDecorator
            )
        );
    }

    /**
     * @param  string $service
     * @return int
     */
    public function recordFailure(string $service): int
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
     * @return int
     */
    public function recordSuccess(string $service): ?int
    {
        $this->storage->incrementSuccessCount($service);
        $status = $this->storage->getStatus($service);
        if (
            $status === Ganesha::STATUS_TRIPPED
            && $this->isClosedInPreviousTimeWindow($service)
        ) {
            $this->storage->setStatus($service, Ganesha::STATUS_CALMED_DOWN);
            return Ganesha::STATUS_CALMED_DOWN;
        }

        return null;
    }

    /**
     * @return void
     */
    public function reset(): void
    {
        $this->storage->reset();
    }

    /**
     * @param  string $service
     * @return bool
     */
    public function isAvailable(string $service): bool
    {
        if ($this->isClosed($service) || $this->isHalfOpen($service)) {
            return true;
        }

        $this->storage->incrementRejectionCount($service);
        return false;
    }

    /**
     * @param  string $service
     * @return bool
     * @throws StorageException
     * @throws \LogicException
     */
    private function isClosed(string $service): bool
    {
        switch (true) {
            case $this->storage->supportSlidingTimeWindow():
                return $this->isClosedInCurrentTimeWindow($service);
                break;
            case $this->storage->supportTumblingTimeWindow():
                return $this->isClosedInCurrentTimeWindow($service) && $this->isClosedInPreviousTimeWindow($service);
                break;
            default:
                throw new LogicException(sprintf(
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
    private function isClosedInCurrentTimeWindow(string $service): bool
    {
        $failure = $this->storage->getFailureCount($service);
        if (
            $failure === 0
            || ($failure / $this->configuration->minimumRequests()) * 100 < $this->configuration->failureRateThreshold()
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
    private function isClosedInPreviousTimeWindow(string $service): bool
    {
        $failure = $this->storage->getFailureCountByCustomKey(self::keyForPreviousTimeWindow($service, $this->configuration->timeWindow()));
        if (
            $failure === 0
            || ($failure / $this->configuration->minimumRequests()) * 100 < $this->configuration->failureRateThreshold()
        ) {
            return true;
        }

        $success = $this->storage->getSuccessCountByCustomKey(self::keyForPreviousTimeWindow($service, $this->configuration->timeWindow()));
        $rejection = $this->storage->getRejectionCountByCustomKey(self::keyForPreviousTimeWindow($service, $this->configuration->timeWindow()));

        return $this->isClosedInTimeWindow($failure, $success, $rejection);
    }

    /**
     * @param  int $failure
     * @param  int $success
     * @param  int $rejection
     * @return bool
     */
    private function isClosedInTimeWindow(int $failure, int $success, int $rejection): bool
    {
        if (($failure + $success + $rejection) < $this->configuration->minimumRequests()) {
            return true;
        }

        if (($failure / ($failure + $success)) * 100 < $this->configuration->failureRateThreshold()) {
            return true;
        }

        return false;
    }

    /**
     * @param  string $service
     * @return bool
     * @throws StorageException
     */
    private function isHalfOpen(string $service): bool
    {
        if (is_null($lastFailureTime = $this->storage->getLastFailureTime($service))) {
            return false;
        }

        if ((time() - $lastFailureTime) > $this->configuration->intervalToHalfOpen()) {
            $this->storage->setLastFailureTime($service, time());
            return true;
        }

        return false;
    }

    private static function serviceNameDecorator(int $timeWindow, $current = true)
    {
        return function ($service) use ($timeWindow, $current) {
            return sprintf(
                '%s.%d',
                $service,
                $current ? (int)floor(time() / $timeWindow) : (int)floor((time() - $timeWindow) / $timeWindow)
            );
        };
    }

    private static function keyForPreviousTimeWindow(string $service, int $timeWindow)
    {
        $f = self::serviceNameDecorator($timeWindow, false);
        return $f($service);
    }
}
