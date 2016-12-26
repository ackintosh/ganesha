<?php
namespace Ackintosh\Ganesha\Storage\Adapter;

use Ackintosh\Ganesha\Storage\AdapterInterface;

class Memcached implements AdapterInterface
{
    /**
     * @var \Memcached
     */
    private $memcached;

    const KEY_SUFFIX_LAST_FAILURE_TIME = 'LastFailureTime';
    const KEY_SUFFIX_STATUS = 'Status';

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
     */
    public function load($serviceName)
    {
        return (int)$this->memcached->get($serviceName);
    }

    /**
     * @param string $serviceName
     * @param int $count
     * @param int    $ttl
     * @return void
     */
    public function save($serviceName, $count, $ttl)
    {
        $this->memcached->set($serviceName, $count, $ttl);
    }

    /**
     * @param string $serviceName
     * @param int    $ttl
     * @return void
     */
    public function increment($serviceName, $ttl)
    {
        // requires \Memcached::OPT_BINARY_PROTOCOL
        $this->memcached->increment($serviceName, 1, 1, $ttl);
    }

    /**
     * @param string $serviceName
     * @param int    $ttl
     * @return void
     */
    public function decrement($serviceName, $ttl)
    {
        // requires \Memcached::OPT_BINARY_PROTOCOL
        $this->memcached->decrement($serviceName, 1, 0, $ttl);
    }

    /**
     * @param string $serviceName
     * @param int    $lastFailureTime
     */
    public function saveLastFailureTime($serviceName, $lastFailureTime)
    {
        $this->memcached->set($serviceName . self::KEY_SUFFIX_LAST_FAILURE_TIME, $lastFailureTime);
    }

    /**
     * @param  string $serviceName
     * @return int
     */
    public function loadLastFailureTime($serviceName)
    {
        return $this->memcached->get($serviceName . self::KEY_SUFFIX_LAST_FAILURE_TIME);
    }

    /**
     * @param string $serviceName
     * @param int    $status
     */
    public function saveStatus($serviceName, $status)
    {
        $this->memcached->set($serviceName . self::KEY_SUFFIX_STATUS, $status);
    }

    /**
     * @param  string $serviceName
     * @return int
     */
    public function loadStatus($serviceName)
    {
        return $this->memcached->get($serviceName . self::KEY_SUFFIX_STATUS);
    }
}
