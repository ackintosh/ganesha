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
     * returns failure count
     *
     * @param  string $serviceName
     * @return int
     * @throws StorageException
     */
    public function getFailureCount($serviceName)
    {
        return $this->getCount($this->key($serviceName) . self::KEY_SUFFIX_FAILURE);
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
        return $this->getCount($this->key($serviceName) . self::KEY_SUFFIX_SUCCESS);
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
        $this->adapter->increment($this->key($serviceName) . self::KEY_SUFFIX_FAILURE, $this->countTTL);
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
        $this->adapter->decrement($this->key($serviceName) . self::KEY_SUFFIX_FAILURE, $this->countTTL);
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
        $this->adapter->increment($this->key($serviceName) . self::KEY_SUFFIX_SUCCESS, $this->countTTL);
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
        $this->adapter->save($this->key($serviceName) . self::KEY_SUFFIX_FAILURE, $failureCount, $this->countTTL);
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
}
