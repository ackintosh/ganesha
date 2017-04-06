<?php
namespace Ackintosh\Ganesha\Storage\Adapter;

use Ackintosh\Ganesha;
use Ackintosh\Ganesha\Exception\StorageException;
use Ackintosh\Ganesha\Storage;
use Ackintosh\Ganesha\Storage\AdapterInterface;

class Memcached implements AdapterInterface
{
    /**
     * @var \Memcached
     */
    private $memcached;

    /**
     * Memcached constructor.
     * @param \Memcached $memcached
     */
    public function __construct(\Memcached $memcached)
    {
        // initial_value in (increment|decrement) requires \Memcached::OPT_BINARY_PROTOCOL
        $memcached->setOption(\Memcached::OPT_BINARY_PROTOCOL, true);
        $this->memcached = $memcached;
    }

    /**
     * @param string $serviceName
     * @return int
     * @throws StorageException
     */
    public function load($serviceName)
    {
        $r = (int)$this->memcached->get($serviceName);
        $this->throwExceptionIfErrorOccurred();
        return $r;
    }

    /**
     * @param string $serviceName
     * @param int $count
     * @param int    $ttl
     * @return void
     * @throws StorageException
     */
    public function save($serviceName, $count, $ttl)
    {
        if (!$this->memcached->set($serviceName, $count, $ttl)) {
            throw new StorageException('failed to set the value : ' . $this->memcached->getResultMessage());
        }
    }

    /**
     * @param string $serviceName
     * @param int    $ttl
     * @return void
     * @throws StorageException
     */
    public function increment($serviceName, $ttl)
    {
        // requires \Memcached::OPT_BINARY_PROTOCOL
        if ($this->memcached->increment($serviceName, 1, 1, $ttl) === false) {
            throw new StorageException('failed to increment failure count : ' . $this->memcached->getResultMessage());
        }
    }

    /**
     * @param string $serviceName
     * @param int    $ttl
     * @return void
     * @throws StorageException
     */
    public function decrement($serviceName, $ttl)
    {
        // requires \Memcached::OPT_BINARY_PROTOCOL
        if ($this->memcached->decrement($serviceName, 1, 0, $ttl) === false) {
            throw new StorageException('failed to decrement failure count : ' . $this->memcached->getResultMessage());
        }
    }

    /**
     * @param string $serviceName
     * @param int    $lastFailureTime
     * @throws StorageException
     */
    public function saveLastFailureTime($serviceName, $lastFailureTime)
    {
        if (!$this->memcached->set($serviceName, $lastFailureTime)) {
            throw new StorageException('failed to set the last failure time : ' . $this->memcached->getResultMessage());
        }
    }

    /**
     * @param  string $serviceName
     * @return int
     * @throws StorageException
     */
    public function loadLastFailureTime($serviceName)
    {
        $r = $this->memcached->get($serviceName);
        $this->throwExceptionIfErrorOccurred();
        return $r;
    }

    /**
     * @param string $serviceName
     * @param int    $status
     * @throws StorageException
     */
    public function saveStatus($serviceName, $status)
    {
        if (!$this->memcached->set($serviceName, $status)) {
            throw new StorageException('failed to set the status : ' . $this->memcached->getResultMessage());
        }
    }

    /**
     * @param  string $serviceName
     * @return int
     * @throws StorageException
     */
    public function loadStatus($serviceName)
    {
        $status = $this->memcached->get($serviceName);
        $this->throwExceptionIfErrorOccurred();
        if ($status === false && $this->memcached->getResultCode() === \Memcached::RES_NOTFOUND) {
            $this->saveStatus($serviceName, Ganesha::STATUS_CALMED_DOWN);
            return Ganesha::STATUS_CALMED_DOWN;
        }

        return $status;
    }

    public function reset()
    {
        $keys = $this->memcached->getAllKeys();
        if (!$keys) {
            $message = sprintf(
                'failed to get memcached keys. resultCode: %d, resultMessage: %s',
                $this->memcached->getResultCode(),
                $this->memcached->getResultMessage()
            );
            throw new \RuntimeException($message);
        }

        foreach ($keys as $k) {
            if ($this->isGaneshaData($k)) {
                $this->memcached->delete($k);
            }
        }
    }

    public function isGaneshaData($key)
    {
        $regex = sprintf(
            '#\A%s.+(%s|%s|%s|%s|%s)\z#',
            Storage::KEY_PREFIX,
            Storage::KEY_SUFFIX_SUCCESS,
            Storage::KEY_SUFFIX_FAILURE,
            Storage::KEY_SUFFIX_REJECTION,
            Storage::KEY_SUFFIX_LAST_FAILURE_TIME,
            Storage::KEY_SUFFIX_STATUS
        );

        return preg_match($regex, $key) === 1;
    }

    /**
     * Throws an exception if some error occurs in memcached.
     *
     * @return void
     * @throws StorageException
     */
    private function throwExceptionIfErrorOccurred()
    {
        $errorResultCodes = array(
            \Memcached::RES_FAILURE,
            \Memcached::RES_SERVER_TEMPORARILY_DISABLED,
            \Memcached::RES_SERVER_MEMORY_ALLOCATION_FAILURE,
        );

        if (in_array($this->memcached->getResultCode(), $errorResultCodes, true)) {
            throw new StorageException($this->memcached->getResultMessage());
        }
    }
}
