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

    /**
     * @param string $resource
     * @return int
     * @throws StorageException
     */
    public function load($resource)
    {
        $expires = microtime(true) - $this->configuration['timeWindow'];

        try {
            if ($this->redis->zRemRangeByScore($resource, '-inf', $expires) === false) {
                throw new StorageException('Failed to remove expired elements. resource: ' . $resource);
            }

            $r =  $this->redis->zCard($resource);
        } catch (\RedisException $e) {
            throw new StorageException($e->getMessage());
        }

        if ($r === false) {
            throw new StorageException('Failed to load cardinality. resource: ' . $resource);
        }

        return $r;
    }

    public function save($resouce, $count)
    {
        // Redis adapter does not support Count strategy
    }

    /**
     * @param string $resource
     * @throws StorageException
     */
    public function increment($resource)
    {
        $t = microtime(true);
        try {
            $r = $this->redis->zAdd($resource, $t, $t);
        } catch (\RedisException $e) {
            throw new StorageException($e->getMessage());
        }

        if ($r === false) {
            throw new StorageException('Failed to add sorted set. resource: ' . $resource);
        }
    }

    public function decrement($resource)
    {
        // Redis adapter does not support Count strategy
    }

    public function saveLastFailureTime($resource, $lastFailureTime)
    {
        // nop
    }

    /**
     * @param $resource
     * @return int|void
     * @throws StorageException
     */
    public function loadLastFailureTime($resource)
    {
        try {
            $lastFailure = $this->redis->zRange($resource, -1, -1);
        } catch (\RedisException $e) {
            throw new StorageException($e->getMessage());
        }

        if (!$lastFailure) {
            return;
        }

        return (int)$lastFailure[0];
    }

    /**
     * @param string $resource
     * @param int $status
     * @throws StorageException
     */
    public function saveStatus($resource, $status)
    {
        try {
            $r = $this->redis->set($resource, $status);
        } catch (\RedisException $e) {
            throw new StorageException($e->getMessage());
        }

        if ($r === false) {
            throw new StorageException(sprintf(
                'Failed to save status. resource: %s, status: %d',
                $resource,
                $status
            ));
        }
    }

    /**
     * @param string $resource
     * @return int
     * @throws StorageException
     */
    public function loadStatus($resource)
    {
        try {
            $r = $this->redis->get($resource);
        } catch (\RedisException $e) {
            throw new StorageException($e->getMessage());
        }

        if ($r === false) {
            throw new StorageException('Failed to load status. resource: ' . $resource);
        }

        return (int)$r;
    }

    public function reset()
    {
        // TODO: Implement reset() method.
    }
}
