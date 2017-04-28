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
    private $serviceNameDecorator;

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
     * @param callable         $serviceNameDecorator
     */
    public function __construct(AdapterInterface $adapter, $serviceNameDecorator)
    {
        $this->adapter = $adapter;
        $this->serviceNameDecorator = $serviceNameDecorator;
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
     * @param  string $serviceName
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
     * @param  string $serviceName
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
     * @param  string $serviceName
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
     * @param  string $serviceName
     * @return int
     * @throws StorageException
     */
    public function getFailureCount($serviceName)
    {
        return $this->getCount($this->failureKey($serviceName));
    }

    /**
     * returns success count
     *
     * @param  string $serviceName
     * @return int
     * @throws StorageException
     */
    public function getSuccessCount($serviceName)
    {
        return $this->getCount($this->successKey($serviceName));
    }

    /**
     * increments failure count
     *
     * @param  string $serviceName
     * @return void
     * @throws StorageException
     */
    public function incrementFailureCount($serviceName)
    {
        $this->adapter->increment($this->failureKey($serviceName));
    }

    /**
     * decrements failure count
     *
     * @param  string $serviceName
     * @return void
     * @throws StorageException
     */
    public function decrementFailureCount($serviceName)
    {
        $this->adapter->decrement($this->failureKey($serviceName));
    }

    /**
     * increments success count
     *
     * @param  string $serviceName
     * @return void
     * @throws StorageException
     */
    public function incrementSuccessCount($serviceName)
    {
        $this->adapter->increment($this->successKey($serviceName));
    }

    /**
     * returns rejection count
     *
     * @param  string $serviceName
     * @return int
     * @throws StorageException
     */
    public function getRejectionCount($serviceName)
    {
        return $this->getCount($this->rejectionKey($serviceName));
    }

    /**
     * increments rejection count
     *
     * @param  string $serviceName
     * @return void
     * @throws StorageException
     */
    public function incrementRejectionCount($serviceName)
    {
        $this->adapter->increment($this->rejectionKey($serviceName));
    }

    /**
     * sets failure count
     *
     * @param $serviceName
     * @param $failureCount
     * @throws StorageException
     */
    public function setFailureCount($serviceName, $failureCount)
    {
        $this->adapter->save($this->failureKey($serviceName), $failureCount);
    }

    /**
     * sets last failure time
     *
     * @param  string $serviceName
     * @param  int    $lastFailureTime
     * @return void
     * @throws StorageException
     */
    public function setLastFailureTime($serviceName, $lastFailureTime)
    {
        $this->adapter->saveLastFailureTime($this->lastFailureKey($serviceName), $lastFailureTime);
    }

    /**
     * returns last failure time
     *
     * @param  string $serviceName
     * @return int | null
     * @throws StorageException
     */
    public function getLastFailureTime($serviceName)
    {
        return $this->adapter->loadLastFailureTime($this->lastFailureKey($serviceName));
    }

    /**
     * sets status
     *
     * @param  string $serviceName
     * @param  int    $status
     * @return void
     * @throws StorageException
     */
    public function setStatus($serviceName, $status)
    {
        $this->adapter->saveStatus($this->statusKey($serviceName), $status);
    }

    /**
     * returns status
     *
     * @param  string $serviceName
     * @return int
     * @throws StorageException
     */
    public function getStatus($serviceName)
    {
        return $this->adapter->loadStatus($this->statusKey($serviceName));
    }

    /**
     * @return void
     */
    public function reset()
    {
        $this->adapter->reset();
    }

    /**
     * @param  string $serviceName
     * @return string
     */
    private function key($serviceName)
    {
        if ($this->serviceNameDecorator) {
            $serviceName = call_user_func($this->serviceNameDecorator, $serviceName);
        }

        return $this->prefix($serviceName);
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
     * @param  string $serviceName
     * @return string
     */
    private function successKey($serviceName)
    {
        return $this->key($serviceName) . self::KEY_SUFFIX_SUCCESS;
    }

    /**
     * @param  string $serviceName
     * @return string
     */
    private function failureKey($serviceName)
    {
        return $this->key($serviceName) . self::KEY_SUFFIX_FAILURE;
    }

    /**
     * @param  string $serviceName
     * @return string
     */
    private function rejectionKey($serviceName)
    {
        return $this->key($serviceName) . self::KEY_SUFFIX_REJECTION;
    }

    /**
     * @param  string $serviceName
     * @return string
     */
    private function lastFailureKey($serviceName)
    {
        return $this->key($serviceName) . self::KEY_SUFFIX_LAST_FAILURE_TIME;
    }

    /**
     * @param  string $serviceName
     * @return string
     */
    private function statusKey($serviceName)
    {
        return $this->key($serviceName) . self::KEY_SUFFIX_STATUS;
    }
}
