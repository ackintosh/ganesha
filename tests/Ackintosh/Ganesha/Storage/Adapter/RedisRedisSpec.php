<?php

namespace Ackintosh\Ganesha\Storage\Adapter;

class RedisRedisSpec extends AbstractRedisSpec
{
    /**
     * @return \Redis
     */
    protected function getRedisConnection(): \Redis
    {
        if (!\extension_loaded('redis')) {
            self::markTestSkipped('No ext-redis present');
        }

        $r = new \Redis();
        $r->connect(
            getenv('GANESHA_EXAMPLE_REDIS') ?: 'localhost'
        );
        $r->flushAll();

        return $r;
    }
}
