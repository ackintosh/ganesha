<?php

namespace Ackintosh\Ganesha\Storage\Adapter;

class RedisRedisTest extends AbstractRedisTest
{
    /**
     * @return \Redis
     */
    protected function getRedisConnection()
    {
        $r = new \Redis();
        $r->connect(
            getenv('GANESHA_EXAMPLE_REDIS') ?: 'localhost'
        );
        $r->flushAll();

        return $r;
    }
}
