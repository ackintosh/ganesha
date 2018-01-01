<?php
namespace Ackintosh\Ganesha\Storage\Adapter;

use Ackintosh\Ganesha\Configuration;
use Ackintosh\Ganesha\Storage\AdapterInterface;

class Redis implements AdapterInterface, RollingTimeWindowInterface
{
    /**
     * @var \Redis
     */
    private $redis;

    /**
     * @var Configuration
     */
    private $configuration;

    public function __construct(\Redis $redis)
    {
        $this->redis = $redis;
    }

    /**
     * @param Configuration $configuration
     * @return void
     */
    public function setConfiguration(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function load($resource)
    {
        $expires = microtime(true) - $this->configuration['timeWindow'];
        $this->redis->zRemRangeByScore($resource, '-inf', $expires);

        return $this->redis->zCard($resource);
    }

    public function save($resouce, $count)
    {
        // Redis adapter does not support Count strategy
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
