<?php

namespace Ackintosh\Ganesha\Storage\Adapter;

use Ackintosh\Ganesha\Configuration;
use Ackintosh\Ganesha\Exception\StorageException;
use Ackintosh\Ganesha\Storage\AdapterInterface;

class Redis implements AdapterInterface, SlidingTimeWindowInterface
{
    /**
     * @var \Ackintosh\Ganesha\Storage\Adapter\RedisStore
     */
    private $redis;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @param \Redis|\RedisArray|\RedisCluster|\Predis\Client|\Ackintosh\Ganesha\Storage\Adapter\RedisStore $redis
     */
    public function __construct($redis)
    {
        if (!($redis instanceof RedisStore)) {
            $redis = new RedisStore($redis);
        }

        $this->redis = $redis;
    }

    /**
     * @param Configuration $configuration
     *
     * @return void
     */
    public function setConfiguration(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @param string $service
     *
     * @return int
     * @throws StorageException
     */
    public function load($service)
    {
        $expires = microtime(true) - $this->configuration['timeWindow'];

        if ($this->redis->zRemRangeByScore($service, '-inf', $expires) === false) {
            throw new StorageException('Failed to remove expired elements. service: ' . $service);
        }

        $r = $this->redis->zCard($service);

        if ($r === false) {
            throw new StorageException('Failed to load cardinality. service: ' . $service);
        }

        return $r;
    }

    public function save($resouce, $count)
    {
        // Redis adapter does not support Count strategy
    }

    /**
     * @param string $service
     *
     * @throws StorageException
     */
    public function increment($service)
    {
        $t = microtime(true);
        $r = $this->redis->zAdd($service, $t, $t);

        if ($r === false) {
            throw new StorageException('Failed to add sorted set. service: ' . $service);
        }
    }

    public function decrement($service)
    {
        // Redis adapter does not support Count strategy
    }

    public function saveLastFailureTime($service, $lastFailureTime)
    {
        // nop
    }

    /**
     * @param $service
     *
     * @return int|void
     * @throws StorageException
     */
    public function loadLastFailureTime($service)
    {
        $lastFailure = $this->redis->zRange($service, -1, -1);

        if (!$lastFailure) {
            return;
        }

        return (int)$lastFailure[0];
    }

    /**
     * @param string $service
     * @param int    $status
     *
     * @throws StorageException
     */
    public function saveStatus($service, $status)
    {
        $r = $this->redis->set($service, $status);

        if ($r === false) {
            throw new StorageException(sprintf(
                'Failed to save status. service: %s, status: %d',
                $service,
                $status
            ));
        }
    }

    /**
     * @param string $service
     *
     * @return int
     * @throws StorageException
     */
    public function loadStatus($service)
    {
        $r = $this->redis->get($service);

        if ($r === false) {
            throw new StorageException('Failed to load status. service: ' . $service);
        }

        return (int)$r;
    }

    public function reset()
    {
        // TODO: Implement reset() method.
    }
}
