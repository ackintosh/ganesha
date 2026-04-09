<?php

namespace Ackintosh\Ganesha\Strategy;

use Ackintosh\Ganesha;
use Ackintosh\Ganesha\Configuration;
use Ackintosh\Ganesha\Exception\StorageException;
use Ackintosh\Ganesha\NativeClock;
use Ackintosh\Ganesha\Storage;
use Ackintosh\Ganesha\StrategyInterface;
use InvalidArgumentException;
use LogicException;
use Psr\Clock\ClockInterface;

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

    private ClockInterface $clock;

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
    private function __construct(
        Configuration $configuration,
        Storage $storage,
        ClockInterface $clock,
    ) {
        $this->configuration = $configuration;
        $this->storage = $storage;
        $this->clock = $clock;
    }

    /**
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

    public static function create(
        Storage\AdapterInterface $adapter,
        Configuration $configuration,
        ?ClockInterface $clock = null
    ): StrategyInterface {
        $clock = $clock ?? new NativeClock();
        $serviceNameDecorator = $adapter instanceof Storage\Adapter\TumblingTimeWindowInterface ? self::serviceNameDecorator($configuration->timeWindow(), $clock) : null;

        return new self(
            $configuration,
            new Storage(
                $adapter,
                $configuration->storageKeys(),
                $serviceNameDecorator
            ),
            $clock,
        );
    }

    public function recordFailure(string $service): int
    {
        $this->storage->setLastFailureTime($service, $this->clock->now()->getTimestamp());
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

    public function reset(): void
    {
        $this->storage->reset();
    }

    public function isAvailable(string $service): bool
    {
        if ($this->isClosed($service) || $this->isHalfOpen($service)) {
            return true;
        }

        $this->storage->incrementRejectionCount($service);
        return false;
    }

    /**
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

    private function isClosedInPreviousTimeWindow(string $service): bool
    {
        $failure = $this->storage->getFailureCountByCustomKey(self::keyForPreviousTimeWindow($service, $this->configuration->timeWindow(), $this->clock));
        if (
            $failure === 0
            || ($failure / $this->configuration->minimumRequests()) * 100 < $this->configuration->failureRateThreshold()
        ) {
            return true;
        }

        $success = $this->storage->getSuccessCountByCustomKey(self::keyForPreviousTimeWindow($service, $this->configuration->timeWindow(), $this->clock));
        $rejection = $this->storage->getRejectionCountByCustomKey(self::keyForPreviousTimeWindow($service, $this->configuration->timeWindow(), $this->clock));

        return $this->isClosedInTimeWindow($failure, $success, $rejection);
    }

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
     * @throws StorageException
     */
    private function isHalfOpen(string $service): bool
    {
        $time = $this->clock->now()->getTimestamp();

        if (is_null($lastFailureTime = $this->storage->getLastFailureTime($service))) {
            return false;
        }

        if (($time - $lastFailureTime) > $this->configuration->intervalToHalfOpen()) {
            $this->storage->setLastFailureTime($service, $time);
            return true;
        }

        return false;
    }

    private static function serviceNameDecorator(int $timeWindow, ClockInterface $clock, bool $current = true): \Closure
    {
        return function ($service) use ($timeWindow, $clock, $current) {
            $time = $clock->now()->getTimestamp();

            return sprintf(
                '%s.%d',
                $service,
                $current ? (int)floor($time / $timeWindow) : (int)floor(($time - $timeWindow) / $timeWindow)
            );
        };
    }

    private static function keyForPreviousTimeWindow(string $service, int $timeWindow, ClockInterface $clock): string
    {
        $f = self::serviceNameDecorator($timeWindow, $clock, false);
        return $f($service);
    }
}
