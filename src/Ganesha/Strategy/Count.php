<?php

namespace Ackintosh\Ganesha\Strategy;

use Ackintosh\Ganesha;
use Ackintosh\Ganesha\Configuration;
use Ackintosh\Ganesha\Exception\StorageException;
use Ackintosh\Ganesha\NativeClock;
use Ackintosh\Ganesha\Storage;
use Ackintosh\Ganesha\StrategyInterface;
use Psr\Clock\ClockInterface;

class Count implements StrategyInterface
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

    private function __construct(
		Configuration $configuration,
		Storage $storage,
		ClockInterface $clock,
	) {
        $this->configuration = $configuration;
        $this->storage = $storage;
		$this->clock = $clock;
    }

    public static function create(
		Storage\AdapterInterface $adapter,
		Configuration $configuration,
		?ClockInterface $clock = null,
	): StrategyInterface {
        return new self(
            $configuration,
            new Storage(
                $adapter,
                $configuration->storageKeys(),
                null
            ),
			$clock ?? new NativeClock(),
        );
    }

    public function recordFailure(string $service): int
    {
        $this->storage->setLastFailureTime($service, $this->clock->now()->getTimestamp());
        $this->storage->incrementFailureCount($service);

        if ($this->storage->getFailureCount($service) >= $this->configuration->failureCountThreshold()
            && $this->storage->getStatus($service) === Ganesha::STATUS_CALMED_DOWN
        ) {
            $this->storage->setStatus($service, Ganesha::STATUS_TRIPPED);
            return Ganesha::STATUS_TRIPPED;
        }

        return Ganesha::STATUS_CALMED_DOWN;
    }

    public function recordSuccess(string $service): ?int
    {
        $this->storage->decrementFailureCount($service);

        $status = $this->storage->getStatus($service);
        if ($this->storage->getFailureCount($service) === 0
            && $status === Ganesha::STATUS_TRIPPED
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
        return $this->isClosed($service) || $this->isHalfOpen($service);
    }

    /**
     * @throws StorageException
     */
    private function isClosed(string $service): bool
    {
        return $this->storage->getFailureCount($service) < $this->configuration->failureCountThreshold();
    }

    /**
     * @throws StorageException
     */
    private function isHalfOpen(string $service): bool
    {
        if (is_null($lastFailureTime = $this->storage->getLastFailureTime($service))) {
            return false;
        }

		$time = $this->clock->now()->getTimestamp();

        if (($time - $lastFailureTime) > $this->configuration->intervalToHalfOpen()) {
            $this->storage->setFailureCount($service, $this->configuration->failureCountThreshold());
            $this->storage->setLastFailureTime($service, $time);
            return true;
        }

        return false;
    }
}
