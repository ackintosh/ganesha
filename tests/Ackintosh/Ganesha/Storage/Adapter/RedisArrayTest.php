<?php

namespace Ackintosh\Ganesha\Storage\Adapter;

use RedisArray;

class RedisArrayTest extends AbstractRedisTest
{
    /**
     * @return \RedisArray
     */
    protected function getRedisConnection()
    {
        $r = new RedisArray([getenv('GANESHA_EXAMPLE_REDIS') ?: 'localhost']);
        $r->flushAll();

        return $r;
    }
}
