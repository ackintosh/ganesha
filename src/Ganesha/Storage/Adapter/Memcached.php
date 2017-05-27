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
     * @param string $resource
     * @return int
     * @throws StorageException
     */
    public function load($resource)
    {
        $r = (int)$this->memcached->get($resource);
        $this->throwExceptionIfErrorOccurred();
        return $r;
    }

    /**
     * @param string $resource
     * @param int $count
     * @return void
     * @throws StorageException
     */
    public function save($resource, $count)
    {
        if (!$this->memcached->set($resource, $count)) {
            throw new StorageException('failed to set the value : ' . $this->memcached->getResultMessage());
        }
    }

    /**
     * @param string $resource
     * @return void
     * @throws StorageException
     */
    public function increment($resource)
    {
        // requires \Memcached::OPT_BINARY_PROTOCOL
        if ($this->memcached->increment($resource, 1, 1) === false) {
            throw new StorageException('failed to increment failure count : ' . $this->memcached->getResultMessage());
        }
    }

    /**
     * @param string $resource
     * @return void
     * @throws StorageException
     */
    public function decrement($resource)
    {
        // requires \Memcached::OPT_BINARY_PROTOCOL
        if ($this->memcached->decrement($resource, 1, 0) === false) {
            throw new StorageException('failed to decrement failure count : ' . $this->memcached->getResultMessage());
        }
    }

    /**
     * @param string $resource
     * @param int    $lastFailureTime
     * @throws StorageException
     */
    public function saveLastFailureTime($resource, $lastFailureTime)
    {
        if (!$this->memcached->set($resource, $lastFailureTime)) {
            throw new StorageException('failed to set the last failure time : ' . $this->memcached->getResultMessage());
        }
    }

    /**
     * @param  string $resource
     * @return int
     * @throws StorageException
     */
    public function loadLastFailureTime($resource)
    {
        $r = $this->memcached->get($resource);
        $this->throwExceptionIfErrorOccurred();
        return $r;
    }

    /**
     * @param string $resource
     * @param int    $status
     * @throws StorageException
     */
    public function saveStatus($resource, $status)
    {
        if (!$this->memcached->set($resource, $status)) {
            throw new StorageException('failed to set the status : ' . $this->memcached->getResultMessage());
        }
    }

    /**
     * @param  string $resource
     * @return int
     * @throws StorageException
     */
    public function loadStatus($resource)
    {
        $status = $this->memcached->get($resource);
        $this->throwExceptionIfErrorOccurred();
        if ($status === false && $this->memcached->getResultCode() === \Memcached::RES_NOTFOUND) {
            $this->saveStatus($resource, Ganesha::STATUS_CALMED_DOWN);
            return Ganesha::STATUS_CALMED_DOWN;
        }

        return $status;
    }

    public function reset()
    {
        if (!$this->memcached->getStats()) {
            throw new \RuntimeException('Couldn\'t connect to memcached.');
        }

        // getAllKeys() with OPT_BINARY_PROTOCOL is not suppoted.
        // So temporarily disable it.
        $this->memcached->setOption(\Memcached::OPT_BINARY_PROTOCOL, false);
        $keys = $this->memcached->getAllKeys();
        $this->memcached->setOption(\Memcached::OPT_BINARY_PROTOCOL, true);
        if (!$keys) {
            $resultCode = $this->memcached->getResultCode();
            if ($resultCode === 0) {
                // no keys
                return;
            }
            $message = sprintf(
                'failed to get memcached keys. resultCode: %d, resultMessage: %s',
                $resultCode,
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
        $errorResultCodes = [
            \Memcached::RES_FAILURE,
            \Memcached::RES_SERVER_TEMPORARILY_DISABLED,
            \Memcached::RES_SERVER_MEMORY_ALLOCATION_FAILURE,
        ];

        if (in_array($this->memcached->getResultCode(), $errorResultCodes, true)) {
            throw new StorageException($this->memcached->getResultMessage());
        }
    }
}
