<?php
namespace Ackintosh\Ganesha;

use Ackintosh\Ganesha\Exception\StorageException;
use Ackintosh\Ganesha\Storage\AdapterInterface;

class Storage
{
    /**
     * @var Storage\AdapterInterface
     */
    private $adapter;

    /**
     * @var callable
     */
    private $resourceDecorator;

    /**
     * @var string
     */
    const KEY_PREFIX = 'ganesha_';

    /**
     * @var string
     */
    const KEY_SUFFIX_SUCCESS = '_success';

    /**
     * @var string
     */
    const KEY_SUFFIX_FAILURE = '_failure';

    /**
     * @var string
     */
    const KEY_SUFFIX_REJECTION = '_rejection';

    /**
     * @var string
     */
    const KEY_SUFFIX_LAST_FAILURE_TIME = '_last_failure_time';

    /**
     * @var string
     */
    const KEY_SUFFIX_STATUS = '_status';

    /**
     * Storage constructor.
     *
     * @param AdapterInterface $adapter
     * @param callable         $resourceDecorator
     */
    public function __construct(AdapterInterface $adapter, $resourceDecorator)
    {
        $this->adapter = $adapter;
        $this->resourceDecorator = $resourceDecorator;
    }

    /**
     * returns count
     *
     * @param  string $key
     * @return int
     * @throws StorageException
     */
    private function getCount($key)
    {
        return $this->adapter->load($key);
    }

    /**
     * returns success count
     *
     * @param  string $resource
     * @return int
     * @throws StorageException
     */
    public function getSuccessCountByCustomKey($key)
    {
        return $this->getCount($this->prefix($key) . self::KEY_SUFFIX_SUCCESS);
    }

    /**
     * returns failure count
     *
     * @param  string $resource
     * @return int
     * @throws StorageException
     */
    public function getFailureCountByCustomKey($key)
    {
        return $this->getCount($this->prefix($key) . self::KEY_SUFFIX_FAILURE);
    }

    /**
     * returns rejection count
     *
     * @param  string $resource
     * @return int
     * @throws StorageException
     */
    public function getRejectionCountByCustomKey($key)
    {
        return $this->getCount($this->prefix($key) . self::KEY_SUFFIX_REJECTION);
    }

    /**
     * returns failure count
     *
     * @param  string $resource
     * @return int
     * @throws StorageException
     */
    public function getFailureCount($resource)
    {
        return $this->getCount($this->failureKey($resource));
    }

    /**
     * returns success count
     *
     * @param  string $resource
     * @return int
     * @throws StorageException
     */
    public function getSuccessCount($resource)
    {
        return $this->getCount($this->successKey($resource));
    }

    /**
     * increments failure count
     *
     * @param  string $resource
     * @return void
     * @throws StorageException
     */
    public function incrementFailureCount($resource)
    {
        $this->adapter->increment($this->failureKey($resource));
    }

    /**
     * decrements failure count
     *
     * @param  string $resource
     * @return void
     * @throws StorageException
     */
    public function decrementFailureCount($resource)
    {
        $this->adapter->decrement($this->failureKey($resource));
    }

    /**
     * increments success count
     *
     * @param  string $resource
     * @return void
     * @throws StorageException
     */
    public function incrementSuccessCount($resource)
    {
        $this->adapter->increment($this->successKey($resource));
    }

    /**
     * returns rejection count
     *
     * @param  string $resource
     * @return int
     * @throws StorageException
     */
    public function getRejectionCount($resource)
    {
        return $this->getCount($this->rejectionKey($resource));
    }

    /**
     * increments rejection count
     *
     * @param  string $resource
     * @return void
     * @throws StorageException
     */
    public function incrementRejectionCount($resource)
    {
        $this->adapter->increment($this->rejectionKey($resource));
    }

    /**
     * sets failure count
     *
     * @param $resource
     * @param $failureCount
     * @throws StorageException
     */
    public function setFailureCount($resource, $failureCount)
    {
        $this->adapter->save($this->failureKey($resource), $failureCount);
    }

    /**
     * sets last failure time
     *
     * @param  string $resource
     * @param  int    $lastFailureTime
     * @return void
     * @throws StorageException
     */
    public function setLastFailureTime($resource, $lastFailureTime)
    {
        $this->adapter->saveLastFailureTime($this->lastFailureKey($resource), $lastFailureTime);
    }

    /**
     * returns last failure time
     *
     * @param  string $resource
     * @return int | null
     * @throws StorageException
     */
    public function getLastFailureTime($resource)
    {
        return $this->adapter->loadLastFailureTime($this->lastFailureKey($resource));
    }

    /**
     * sets status
     *
     * @param  string $resource
     * @param  int    $status
     * @return void
     * @throws StorageException
     */
    public function setStatus($resource, $status)
    {
        $this->adapter->saveStatus($this->statusKey($resource), $status);
    }

    /**
     * returns status
     *
     * @param  string $resource
     * @return int
     * @throws StorageException
     */
    public function getStatus($resource)
    {
        return $this->adapter->loadStatus($this->statusKey($resource));
    }

    /**
     * @return void
     */
    public function reset()
    {
        $this->adapter->reset();
    }

    /**
     * @param  string $resource
     * @return string
     */
    private function key($resource)
    {
        if ($this->resourceDecorator) {
            $resource = call_user_func($this->resourceDecorator, $resource);
        }

        return $this->prefix($resource);
    }

    /**
     * @param  string $key
     * @return string
     */
    private function prefix($key)
    {
        return self::KEY_PREFIX . $key;
    }

    /**
     * @param  string $resource
     * @return string
     */
    private function successKey($resource)
    {
        return $this->key($resource) . self::KEY_SUFFIX_SUCCESS;
    }

    /**
     * @param  string $resource
     * @return string
     */
    private function failureKey($resource)
    {
        return $this->key($resource) . self::KEY_SUFFIX_FAILURE;
    }

    /**
     * @param  string $resource
     * @return string
     */
    private function rejectionKey($resource)
    {
        return $this->key($resource) . self::KEY_SUFFIX_REJECTION;
    }

    /**
     * @param  string $resource
     * @return string
     */
    private function lastFailureKey($resource)
    {
        return $this->prefix($resource) . self::KEY_SUFFIX_LAST_FAILURE_TIME;
    }

    /**
     * @param  string $resource
     * @return string
     */
    private function statusKey($resource)
    {
        return $this->prefix($resource) . self::KEY_SUFFIX_STATUS;
    }
}
