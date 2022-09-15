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

    /**
     * Storage constructor.
     *
     * @param AdapterInterface $adapter
     * @param StorageKeysInterface $storageKeys
     * @param callable|null $serviceNameDecorator
     */
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
     * returns count
     *
     * @param  string $key
     * @return int
     * @throws StorageException
     */
    private function getCount(string $key): int
    {
        return $this->adapter->load($key);
    }

    /**
     * returns success count
     *
     * @param  string $key
     * @return int
     * @throws StorageException
     */
    public function getSuccessCountByCustomKey(string $key): int
    {
        return $this->getCount($this->prefix($key) . $this->storageKeys->success());
    }

    /**
     * returns failure count
     *
     * @param  string $key
     * @return int
     * @throws StorageException
     */
    public function getFailureCountByCustomKey(string $key): int
    {
        return $this->getCount($this->prefix($key) . $this->storageKeys->failure());
    }

    /**
     * returns rejection count
     *
     * @param  string $key
     * @return int
     * @throws StorageException
     */
    public function getRejectionCountByCustomKey(string $key): int
    {
        return $this->getCount($this->prefix($key) . $this->storageKeys->rejection());
    }

    /**
     * returns failure count
     *
     * @param  string $service
     * @return int
     * @throws StorageException
     */
    public function getFailureCount(string $service): int
    {
        return $this->getCount($this->failureKey($service));
    }

    /**
     * returns success count
     *
     * @param  string $service
     * @return int
     * @throws StorageException
     */
    public function getSuccessCount(string $service): int
    {
        return $this->getCount($this->successKey($service));
    }

    /**
     * increments failure count
     *
     * @param  string $service
     * @return void
     * @throws StorageException
     */
    public function incrementFailureCount(string $service): void
    {
        $this->adapter->increment($this->failureKey($service));
    }

    /**
     * decrements failure count
     *
     * @param  string $service
     * @return void
     * @throws StorageException
     */
    public function decrementFailureCount(string $service): void
    {
        $this->adapter->decrement($this->failureKey($service));
    }

    /**
     * increments success count
     *
     * @param  string $service
     * @return void
     * @throws StorageException
     */
    public function incrementSuccessCount(string $service): void
    {
        $this->adapter->increment($this->successKey($service));
    }

    /**
     * returns rejection count
     *
     * @param  string $service
     * @return int
     * @throws StorageException
     */
    public function getRejectionCount(string $service): int
    {
        return $this->getCount($this->rejectionKey($service));
    }

    /**
     * increments rejection count
     *
     * @param  string $service
     * @return void
     * @throws StorageException
     */
    public function incrementRejectionCount(string $service): void
    {
        $this->adapter->increment($this->rejectionKey($service));
    }

    /**
     * sets failure count
     *
     * @param $service
     * @param $failureCount
     * @throws StorageException
     */
    public function setFailureCount(string $service, int $failureCount): void
    {
        $this->adapter->save($this->failureKey($service), $failureCount);
    }

    /**
     * sets last failure time
     *
     * @param  string $service
     * @param  int    $lastFailureTime
     * @return void
     * @throws StorageException
     */
    public function setLastFailureTime(string $service, int $lastFailureTime): void
    {
        $this->adapter->saveLastFailureTime($this->lastFailureKey($service), $lastFailureTime);
    }

    /**
     * returns last failure time
     *
     * @param  string $service
     * @return int | null
     * @throws StorageException
     */
    public function getLastFailureTime(string $service)
    {
        return $this->adapter->loadLastFailureTime($this->lastFailureKey($service));
    }

    /**
     * sets status
     *
     * @param  string $service
     * @param  int    $status
     * @return void
     * @throws StorageException
     */
    public function setStatus(string $service, int $status): void
    {
        $this->adapter->saveStatus($this->statusKey($service), $status);
    }

    /**
     * returns status
     *
     * @param  string $service
     * @return int
     * @throws StorageException
     */
    public function getStatus(string $service): int
    {
        return $this->adapter->loadStatus($this->statusKey($service));
    }

    /**
     * @return void
     */
    public function reset(): void
    {
        $this->adapter->reset();
    }

    /**
     * @return bool
     */
    public function supportTumblingTimeWindow(): bool
    {
        return $this->adapter instanceof TumblingTimeWindowInterface;
    }

    /**
     * @return bool
     */
    public function supportSlidingTimeWindow(): bool
    {
        return $this->adapter instanceof SlidingTimeWindowInterface;
    }

    /**
     * @param  string $service
     * @return string
     */
    private function key(string $service): string
    {
        if ($this->serviceNameDecorator) {
            $service = call_user_func($this->serviceNameDecorator, $service);
        }

        return $this->prefix($service);
    }

    /**
     * @param  string $key
     * @return string
     */
    private function prefix(string $key): string
    {
        return $this->storageKeys->prefix() . $key;
    }

    /**
     * @param  string $service
     * @return string
     */
    private function successKey(string $service): string
    {
        return $this->key($service) . $this->storageKeys->success();
    }

    /**
     * @param  string $service
     * @return string
     */
    private function failureKey(string $service): string
    {
        return $this->key($service) . $this->storageKeys->failure();
    }

    /**
     * @param  string $service
     * @return string
     */
    private function rejectionKey(string $service): string
    {
        return $this->key($service) . $this->storageKeys->rejection();
    }

    /**
     * @param  string $service
     * @return string
     */
    private function lastFailureKey(string $service): string
    {
        return $this->supportSlidingTimeWindow()
            // If the adapter supports SlidingTimeWindow use failureKey() instead,
            // because Redis doesn't save lastFailureTime.
            // @see Ackintosh\Ganesha\Storage\Adapter\Redis#saveLastFailureTime()
            ? $this->failureKey($service)
            : $this->prefix($service) . $this->storageKeys->lastFailureTime();
    }

    /**
     * @param  string $service
     * @return string
     */
    private function statusKey(string $service): string
    {
        return $this->prefix($service) . $this->storageKeys->status();
    }
}
