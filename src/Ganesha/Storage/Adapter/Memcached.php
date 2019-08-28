<?php
namespace Ackintosh\Ganesha\Storage\Adapter;

use Ackintosh\Ganesha;
use Ackintosh\Ganesha\Configuration;
use Ackintosh\Ganesha\Exception\StorageException;
use Ackintosh\Ganesha\Storage;
use Ackintosh\Ganesha\Storage\AdapterInterface;

class Memcached implements AdapterInterface, TumblingTimeWindowInterface
{
    /**
     * @var \Memcached
     */
    private $memcached;

    /**
     * @var Configuration
     */
    private $configuration;

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
     * @return bool
     */
    public function supportCountStrategy()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function supportRateStrategy()
    {
        return true;
    }

    /**
     * @param Configuration $configuration
     * @return void
     */
    public function setConfiguration(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @param string $service
     * @return int
     * @throws StorageException
     */
    public function load($service)
    {
        $r = (int)$this->memcached->get($service);
        $this->throwExceptionIfErrorOccurred();
        return $r;
    }

    /**
     * @param string $service
     * @param int $count
     * @return void
     * @throws StorageException
     */
    public function save($service, $count)
    {
        if (!$this->memcached->set($service, $count)) {
            throw new StorageException('failed to set the value : ' . $this->memcached->getResultMessage());
        }
    }

    /**
     * @param string $service
     * @return void
     * @throws StorageException
     */
    public function increment($service)
    {
        // requires \Memcached::OPT_BINARY_PROTOCOL
        if ($this->memcached->increment($service, 1, 1) === false) {
            throw new StorageException('failed to increment failure count : ' . $this->memcached->getResultMessage());
        }
    }

    /**
     * @param string $service
     * @return void
     * @throws StorageException
     */
    public function decrement($service)
    {
        // requires \Memcached::OPT_BINARY_PROTOCOL
        if ($this->memcached->decrement($service, 1, 0) === false) {
            throw new StorageException('failed to decrement failure count : ' . $this->memcached->getResultMessage());
        }
    }

    /**
     * @param string $service
     * @param int    $lastFailureTime
     * @throws StorageException
     */
    public function saveLastFailureTime($service, $lastFailureTime)
    {
        if (!$this->memcached->set($service, $lastFailureTime)) {
            throw new StorageException('failed to set the last failure time : ' . $this->memcached->getResultMessage());
        }
    }

    /**
     * @param  string $service
     * @return int
     * @throws StorageException
     */
    public function loadLastFailureTime($service)
    {
        $r = $this->memcached->get($service);
        $this->throwExceptionIfErrorOccurred();
        return $r;
    }

    /**
     * @param string $service
     * @param int    $status
     * @throws StorageException
     */
    public function saveStatus($service, $status)
    {
        if (!$this->memcached->set($service, $status)) {
            throw new StorageException('failed to set the status : ' . $this->memcached->getResultMessage());
        }
    }

    /**
     * @param  string $service
     * @return int
     * @throws StorageException
     */
    public function loadStatus($service)
    {
        $status = $this->memcached->get($service);
        $this->throwExceptionIfErrorOccurred();
        if ($status === false && $this->memcached->getResultCode() === \Memcached::RES_NOTFOUND) {
            $this->saveStatus($service, Ganesha::STATUS_CALMED_DOWN);
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
            Storage\StorageKeys::KEY_PREFIX,
            Storage\StorageKeys::KEY_SUFFIX_SUCCESS,
            Storage\StorageKeys::KEY_SUFFIX_FAILURE,
            Storage\StorageKeys::KEY_SUFFIX_REJECTION,
            Storage\StorageKeys::KEY_SUFFIX_LAST_FAILURE_TIME,
            Storage\StorageKeys::KEY_SUFFIX_STATUS
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
