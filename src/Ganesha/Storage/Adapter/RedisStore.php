<?php

namespace Ackintosh\Ganesha\Storage\Adapter;

use Ackintosh\Ganesha\Exception\StorageException;
use Exception;

class RedisStore
{
    /**
     * @var \Predis\ClientInterface|\Redis|\RedisArray|\RedisCluster
     */
    private $redis;

    /**
     * @param \Redis|\RedisArray|\RedisCluster|\Predis\ClientInterface $redisClient
     *
     * @throws \InvalidArgumentException if redis client is not supported
     */
    public function __construct($redisClient)
    {
        if (
            !$redisClient instanceof \Redis
            && !$redisClient instanceof \RedisArray
            && !$redisClient instanceof \RedisCluster
            && !$redisClient instanceof \Predis\ClientInterface
        ) {
            throw new \InvalidArgumentException(sprintf(
                '%s() expects parameter 1 to be Redis, RedisArray, RedisCluster, or \Predis\ClientInterface  %s given',
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
     * @throws StorageException
     */
    public function zRemRangeByScore(string $key, $start, $end)
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
     * @throws StorageException
     */
    public function zCard(string $key): int
    {
        try {
            $r = $this->redis->zCard($key);
        } catch (Exception $exception) {
            throw new StorageException($exception->getMessage(), $exception->getCode(), $exception);
        }

        if ($r === false) {
            throw new StorageException(sprintf(
                "Failed to execute zCard command. key: %s",
                $key
            ));
        }

        return $r;
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
     * @throws StorageException
     */
    public function zAdd(string $key, float $score1, string $value1): int
    {
        try {
            $r = $this->redis->zAdd($key, $score1, $value1);
        } catch (Exception $exception) {
            throw new StorageException($exception->getMessage(), $exception->getCode(), $exception);
        }

        if ($r === false) {
            throw new StorageException(sprintf(
                "Failed to execute zAdd command. key: %s, score1: %s, value1: %s",
                $key,
                $score1,
                $value1
            ));
        }

        return $r;
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
     * @throws StorageException
     */
    public function zRange(string $key, int $start, int $end): array
    {
        try {
            $elements = $this->redis->zRange($key, $start, $end);
            return !$elements ? [] : $elements;
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
     * @throws StorageException
     */
    public function set(string $key, string $value): bool
    {
        try {
            $r = $this->redis->set($key, $value);
            if (is_bool($r)) {
                return $r;
            } elseif ($r instanceof \Predis\Response\Status) {
                return $r->getPayload() === 'OK';
            } else {
                throw new \LogicException("Could not handle the response: " . serialize($r));
            }
        } catch (Exception $exception) {
            throw new StorageException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * Get the value related to the specified key
     *
     * @param   string $key
     *
     * @return  string|false  If key didn't exist, FALSE is returned. Otherwise, the value related to this key is returned.
     *
     * @throws StorageException
     */
    public function get(string $key)
    {
        try {
            $result = $this->redis->get($key);
            if ($this->redis instanceof \Predis\ClientInterface && $result === null) {
                return false;
            }
            return $result;
        } catch (Exception $exception) {
            throw new StorageException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
