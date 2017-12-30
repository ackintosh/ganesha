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
        $this->redis->zAdd($resource, microtime(true), '1');
    }

    public function decrement($resource)
    {
        // TODO: Implement decrement() method.
    }

    public function saveLastFailureTime($resource, $lastFailureTime)
    {
        // TODO: Implement saveLastFailureTime() method.
    }

    public function loadLastFailureTime($resource)
    {
        // TODO: Implement loadLastFailureTime() method.
    }

    public function saveStatus($resource, $status)
    {
        // TODO: Implement saveStatus() method.
    }

    public function loadStatus($resource)
    {
        // TODO: Implement loadStatus() method.
    }

    public function reset()
    {
        // TODO: Implement reset() method.
    }
}
