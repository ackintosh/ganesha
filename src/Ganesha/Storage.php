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
     * @var int
     */
    private $countTTL;

    /**
     * @var callable
     */
    private $serviceNameDecorator;

    /**
     * @var string
     */
    const KEY_SUFFIX_SUCCESS = '.success';

    /**
     * @var string
     */
    const KEY_SUFFIX_FAILURE = '.failure';

    /**
     * @var string
     */
    const KEY_SUFFIX_REJECTION = '.rejection';

    /**
     * Storage constructor.
     *
     * @param AdapterInterface $adapter
     * @param int              $countTTL
     */
    public function __construct(AdapterInterface $adapter, $countTTL, $serviceNameDecorator)
    {
        $this->adapter = $adapter;
        $this->countTTL = $countTTL;
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
        return $this->getCount($key . self::KEY_SUFFIX_SUCCESS);
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
        return $this->getCount($key . self::KEY_SUFFIX_FAILURE);
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
        return $this->getCount($key . self::KEY_SUFFIX_REJECTION);
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
        $this->adapter->increment($this->failureKey($serviceName), $this->countTTL);
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
        $this->adapter->decrement($this->failureKey($serviceName), $this->countTTL);
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
        $this->adapter->increment($this->successKey($serviceName), $this->countTTL);
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
        $this->adapter->increment($this->rejectionKey($serviceName), $this->countTTL);
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
        $this->adapter->save($this->failureKey($serviceName), $failureCount, $this->countTTL);
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
        $this->adapter->saveLastFailureTime($this->key($serviceName), $lastFailureTime);
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
        return $this->adapter->loadLastFailureTime($this->key($serviceName));
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
        $this->adapter->saveStatus($this->key($serviceName), $status);
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
        return $this->adapter->loadStatus($this->key($serviceName));
    }

    /**
     * @param  string $serviceName
     * @return string
     */
    private function key($serviceName)
    {
        if ($this->serviceNameDecorator) {
            return call_user_func($this->serviceNameDecorator, $serviceName);
        }

        return $serviceName;
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
}
