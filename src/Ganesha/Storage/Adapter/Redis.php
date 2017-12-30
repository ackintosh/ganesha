<?php
namespace Ackintosh\Ganesha\Storage\Adapter;

use Ackintosh\Ganesha\Storage\AdapterInterface;

class Redis implements AdapterInterface
{
    /**
     * @var \Redis
     */
    private $redis;

    public function __construct(\Redis $redis)
    {
        $this->redis = $redis;
    }

    public function load($resource)
    {
        return $this->redis->zCard($resource);
    }

    public function save($resouce, $count)
    {
    }

    public function increment($resource)
    {
        $t = microtime(true);
        $this->redis->zAdd($resource, $t, $t);
    }

    public function decrement($resource)
    {
        // Redis adapter does not support Count strategy
    }

    public function saveLastFailureTime($resource, $lastFailureTime)
    {
        // nop
    }

    public function loadLastFailureTime($resource)
    {
        $lastFailure = $this->redis->zRange($resource, -1, -1);

        if (!$lastFailure) {
            return;
        }

        return (int)$lastFailure[0];
    }

    public function saveStatus($resource, $status)
    {
        $this->redis->set($resource, $status);
    }

    public function loadStatus($resource)
    {
        return (int)$this->redis->get($resource);
    }

    public function reset()
    {
        // TODO: Implement reset() method.
    }
}
