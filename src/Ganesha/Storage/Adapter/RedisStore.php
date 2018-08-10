<?php

namespace Ackintosh\Ganesha\Storage\Adapter;

use Ackintosh\Ganesha\Exception\StorageException;
use Exception;

class RedisStore
{
    /**
     * @var \Predis\Client|\Redis|\RedisArray|\RedisCluster
     */
    private $redis;

    /**
     * @param \Redis|\RedisArray|\RedisCluster|\Predis\Client $redisClient
     *
     * @throws \InvalidArgumentException if redis client is not supported
     */
    public function __construct($redisClient)
    {
        if (
            !$redisClient instanceof \Redis
            && !$redisClient instanceof \RedisArray
            && !$redisClient instanceof \RedisCluster
            && !$redisClient instanceof \Predis\Client
        ) {
            throw new \InvalidArgumentException(sprintf(
                '%s() expects parameter 1 to be Redis, RedisArray, RedisCluster or Predis\Client, %s given',
                __METHOD__,
                \is_object($redisClient) ? \get_class($redisClient) : \gettype($redisClient)
            ));
        }

        $this->redis = $redisClient;
    }

    /**
     * Deletes the elements of the sorted set stored at the specified key which have scores in the range [start,end].
     *
     * @param   string       $key
     * @param   float|string $start double or "+inf" or "-inf" string
     * @param   float|string $end   double or "+inf" or "-inf" string
     *
     * @return  int|false             The number of values deleted from the sorted set
     *
     * @throws \Ackintosh\Ganesha\Exception\StorageException
     */
    public function zRemRangeByScore($key, $start, $end)
    {
        try {
            return $this->redis->zRemRangeByScore($key, $start, $end);
        } catch (Exception $exception) {
            throw new StorageException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * Returns the cardinality of an ordered set.
     *
     * @param   string $key
     *
     * @return  int     the set's cardinality
     *
     * @throws \Ackintosh\Ganesha\Exception\StorageException
     */
    public function zCard($key)
    {
        try {
            return $this->redis->zCard($key);
        } catch (Exception $exception) {
            throw new StorageException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * Adds the specified member with a given score to the sorted set stored at key.
     *
     * @param   string $key    Required key
     * @param   float  $score1 Required score
     * @param   string $value1 Required value
     *
     * @return  int     Number of values added
     *
     * @throws \Ackintosh\Ganesha\Exception\StorageException
     */
    public function zAdd($key, $score1, $value1)
    {
        try {
            return $this->redis->zAdd($key, $score1, $value1);
        } catch (Exception $exception) {
            throw new StorageException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * Returns a range of elements from the ordered set stored at the specified key,
     * with values in the range [start, end]. start and stop are interpreted as zero-based indices:
     * 0 the first element,
     * 1 the second ...
     * -1 the last element,
     * -2 the penultimate ...
     *
     * @param   string $key
     * @param   int    $start
     * @param   int    $end
     *
     * @return  array   Array containing the values in specified range.
     *
     * @throws \Ackintosh\Ganesha\Exception\StorageException
     */
    public function zRange($key, $start, $end)
    {
        try {
            return $this->redis->zRange($key, $start, $end);
        } catch (Exception $exception) {
            throw new StorageException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * Set the string value in argument as value of the key.
     *
     * @param   string $key
     * @param   string $value
     *
     * @return  bool    TRUE if the command is successful.
     *
     * @throws \Ackintosh\Ganesha\Exception\StorageException
     */
    public function set($key, $value)
    {
        try {
            return $this->redis->set($key, $value);
        } catch (Exception $exception) {
            throw new StorageException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * Get the value related to the specified key
     *
     * @param   string $key
     *
     * @return  string|bool  If key didn't exist, FALSE is returned. Otherwise, the value related to this key is returned.
     *
     * @throws \Ackintosh\Ganesha\Exception\StorageException
     */
    public function get($key)
    {
        try {
            return $this->redis->get($key);
        } catch (Exception $exception) {
            throw new StorageException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
