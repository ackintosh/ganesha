<?php

namespace Ackintosh\Ganesha;

use Ackintosh\Ganesha\Exception\StorageException;
use Ackintosh\Ganesha\Storage\Adapter\TumblingTimeWindowInterface;
use Ackintosh\Ganesha\Storage\Adapter\SlidingTimeWindowInterface;
use Ackintosh\Ganesha\Storage\AdapterInterface;
use Ackintosh\Ganesha\Storage\StorageKeysInterface;

class Storage
{
    /**
     * @var Storage\AdapterInterface
     */
    private $adapter;

    /**
     * @var callable|null
     */
    private $serviceNameDecorator;

    /**
     * @var StorageKeysInterface
     */
    private $storageKeys;

    public function __construct(
        AdapterInterface $adapter,
        StorageKeysInterface $storageKeys,
        callable $serviceNameDecorator = null
    ) {
        $this->adapter = $adapter;
        $this->serviceNameDecorator = $serviceNameDecorator;
        $this->storageKeys = $storageKeys;
    }

    /**
     * Returns count
     *
     * @throws StorageException
     */
    private function getCount(string $key): int
    {
        return $this->adapter->load($key);
    }

    /**
     * Returns success count
     *
     * @throws StorageException
     */
    public function getSuccessCountByCustomKey(string $key): int
    {
        return $this->getCount($this->prefix($key) . $this->storageKeys->success());
    }

    /**
     * Returns failure count
     *
     * @throws StorageException
     */
    public function getFailureCountByCustomKey(string $key): int
    {
        return $this->getCount($this->prefix($key) . $this->storageKeys->failure());
    }

    /**
     * Returns rejection count
     *
     * @throws StorageException
     */
    public function getRejectionCountByCustomKey(string $key): int
    {
        return $this->getCount($this->prefix($key) . $this->storageKeys->rejection());
    }

    /**
     * Returns failure count
     *
     * @throws StorageException
     */
    public function getFailureCount(string $service): int
    {
        return $this->getCount($this->failureKey($service));
    }

    /**
     * Returns success count
     *
     * @throws StorageException
     */
    public function getSuccessCount(string $service): int
    {
        return $this->getCount($this->successKey($service));
    }

    /**
     * Increments failure count
     *
     * @throws StorageException
     */
    public function incrementFailureCount(string $service): void
    {
        $this->adapter->increment($this->failureKey($service));
    }

    /**
     * Decrements failure count
     *
     * @throws StorageException
     */
    public function decrementFailureCount(string $service): void
    {
        $this->adapter->decrement($this->failureKey($service));
    }

    /**
     * Increments success count
     *
     * @throws StorageException
     */
    public function incrementSuccessCount(string $service): void
    {
        $this->adapter->increment($this->successKey($service));
    }

    /**
     * Returns rejection count
     *
     * @throws StorageException
     */
    public function getRejectionCount(string $service): int
    {
        return $this->getCount($this->rejectionKey($service));
    }

    /**
     * Increments rejection count
     *
     * @throws StorageException
     */
    public function incrementRejectionCount(string $service): void
    {
        $this->adapter->increment($this->rejectionKey($service));
    }

    /**
     * Sets failure count
     *
     * @throws StorageException
     */
    public function setFailureCount(string $service, int $failureCount): void
    {
        $this->adapter->save($this->failureKey($service), $failureCount);
    }

    /**
     * Sets last failure time
     *
     * @throws StorageException
     */
    public function setLastFailureTime(string $service, int $lastFailureTime): void
    {
        $this->adapter->saveLastFailureTime($this->lastFailureKey($service), $lastFailureTime);
    }

    /**
     * Returns last failure time
     *
     * @return int | null
     * @throws StorageException
     */
    public function getLastFailureTime(string $service)
    {
        return $this->adapter->loadLastFailureTime($this->lastFailureKey($service));
    }

    /**
     * Sets status
     *
     * @throws StorageException
     */
    public function setStatus(string $service, int $status): void
    {
        $this->adapter->saveStatus($this->statusKey($service), $status);
    }

    /**
     * Returns status
     *
     * @throws StorageException
     */
    public function getStatus(string $service): int
    {
        return $this->adapter->loadStatus($this->statusKey($service));
    }

    public function reset(): void
    {
        $this->adapter->reset();
    }

    public function supportTumblingTimeWindow(): bool
    {
        return $this->adapter instanceof TumblingTimeWindowInterface;
    }

    public function supportSlidingTimeWindow(): bool
    {
        return $this->adapter instanceof SlidingTimeWindowInterface;
    }

    private function key(string $service): string
    {
        if ($this->serviceNameDecorator) {
            $service = call_user_func($this->serviceNameDecorator, $service);
        }

        return $this->prefix($service);
    }

    private function prefix(string $key): string
    {
        return $this->storageKeys->prefix() . $key;
    }

    private function successKey(string $service): string
    {
        return $this->key($service) . $this->storageKeys->success();
    }

    private function failureKey(string $service): string
    {
        return $this->key($service) . $this->storageKeys->failure();
    }

    private function rejectionKey(string $service): string
    {
        return $this->key($service) . $this->storageKeys->rejection();
    }

    private function lastFailureKey(string $service): string
    {
        return $this->supportSlidingTimeWindow()
            // If the adapter supports SlidingTimeWindow use failureKey() instead,
            // because Redis doesn't save lastFailureTime.
            // @see Ackintosh\Ganesha\Storage\Adapter\Redis#saveLastFailureTime()
            ? $this->failureKey($service)
            : $this->prefix($service) . $this->storageKeys->lastFailureTime();
    }

    private function statusKey(string $service): string
    {
        return $this->prefix($service) . $this->storageKeys->status();
    }
}
