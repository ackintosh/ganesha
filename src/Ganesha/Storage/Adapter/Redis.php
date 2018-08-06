<?php
namespace Ackintosh\Ganesha\Storage\Adapter;

use Ackintosh\Ganesha\Configuration;
use Ackintosh\Ganesha\Exception\StorageException;
use Ackintosh\Ganesha\Storage\AdapterInterface;

class Redis implements AdapterInterface, SlidingTimeWindowInterface
{
    /**
     * @var \Redis
     */
    private $redis;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @param $redis \Redis|\RedisArray
     */
    public function __construct($redis)
    {
        if (!$redis instanceof \Redis && !$redis instanceof \RedisArray) {
            throw new \InvalidArgumentException(sprintf('%s() expects parameter 1 to be Redis, RedisArray', __METHOD__));
        }

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

    /**
     * @param string $service
     * @return int
     * @throws StorageException
     */
    public function load($service)
    {
        $expires = microtime(true) - $this->configuration['timeWindow'];

        try {
            if ($this->redis->zRemRangeByScore($service, '-inf', $expires) === false) {
                throw new StorageException('Failed to remove expired elements. service: ' . $service);
            }

            $r =  $this->redis->zCard($service);
        } catch (\RedisException $e) {
            throw new StorageException($e->getMessage());
        }

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
     * @throws StorageException
     */
    public function increment($service)
    {
        $t = microtime(true);
        try {
            $r = $this->redis->zAdd($service, $t, $t);
        } catch (\RedisException $e) {
            throw new StorageException($e->getMessage());
        }

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
     * @return int|void
     * @throws StorageException
     */
    public function loadLastFailureTime($service)
    {
        try {
            $lastFailure = $this->redis->zRange($service, -1, -1);
        } catch (\RedisException $e) {
            throw new StorageException($e->getMessage());
        }

        if (!$lastFailure) {
            return;
        }

        return (int)$lastFailure[0];
    }

    /**
     * @param string $service
     * @param int $status
     * @throws StorageException
     */
    public function saveStatus($service, $status)
    {
        try {
            $r = $this->redis->set($service, $status);
        } catch (\RedisException $e) {
            throw new StorageException($e->getMessage());
        }

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
     * @return int
     * @throws StorageException
     */
    public function loadStatus($service)
    {
        try {
            $r = $this->redis->get($service);
        } catch (\RedisException $e) {
            throw new StorageException($e->getMessage());
        }

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
