<?php
namespace Ackintosh\Ganesha\Strategy;

use Ackintosh\Ganesha;
use Ackintosh\Ganesha\Configuration;
use Ackintosh\Ganesha\Exception\StorageException;
use Ackintosh\Ganesha\Storage;
use Ackintosh\Ganesha\StrategyInterface;
use InvalidArgumentException;
use LogicException;

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

    /**
     * @var array
     */
    private static $requirements = [
        Configuration::ADAPTER,
        Configuration::FAILURE_COUNT_THRESHOLD,
        Configuration::INTERVAL_TO_HALF_OPEN,
    ];

    /**
     * @param Configuration $configuration
     * @param Storage $storage
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

        if (!call_user_func([$params['adapter'], 'supportCountStrategy'])) {
            throw new InvalidArgumentException(get_class($params['adapter'])  . " doesn't support Count Strategy.");
        }
    }

    /**
     * @param Configuration $configuration
     * @return Count
     */
    public static function create(Configuration $configuration): StrategyInterface
    {
        return new self(
            $configuration,
            new Storage(
                $configuration['adapter'],
                $configuration['storageKeys'],
                null
            )
        );
    }

    /**
     * @param string $service
     * @return int
     */
    public function recordFailure(string $service): int
    {
        $this->storage->setLastFailureTime($service, time());
        $this->storage->incrementFailureCount($service);

        if ($this->storage->getFailureCount($service) >= $this->configuration['failureCountThreshold']
            && $this->storage->getStatus($service) === Ganesha::STATUS_CALMED_DOWN
        ) {
            $this->storage->setStatus($service, Ganesha::STATUS_TRIPPED);
            return Ganesha::STATUS_TRIPPED;
        }

        return Ganesha::STATUS_CALMED_DOWN;
    }

    /**
     * @param string $service
     * @return int
     */
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

    /**
     * @return void
     */
    public function reset(): void
    {
        $this->storage->reset();
    }

    /**
     * @param string $service
     * @return bool
     */
    public function isAvailable(string $service): bool
    {
        return $this->isClosed($service) || $this->isHalfOpen($service);
    }

    /**
     * @param string $service
     * @return bool
     * @throws StorageException
     */
    private function isClosed(string $service): bool
    {
        return $this->storage->getFailureCount($service) < $this->configuration['failureCountThreshold'];
    }

    /**
     * @param string $service
     * @return bool
     * @throws StorageException
     */
    private function isHalfOpen(string $service): bool
    {
        if (is_null($lastFailureTime = $this->storage->getLastFailureTime($service))) {
            return false;
        }

        if ((time() - $lastFailureTime) > $this->configuration['intervalToHalfOpen']) {
            $this->storage->setFailureCount($service, $this->configuration['failureCountThreshold']);
            $this->storage->setLastFailureTime($service, time());
            return true;
        }

        return false;
    }
}
