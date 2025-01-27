<?php

namespace Ackintosh\Ganesha\Storage\Adapter;

use RedisArray;

class RedisArraySpec extends AbstractRedisSpec
{
    /**
     * @return RedisArray
     */
    protected function getRedisConnection(): RedisArray
    {
        if (!\extension_loaded('redis')) {
            self::markTestSkipped('No ext-redis present');
        }

        $r = new RedisArray([getenv('GANESHA_EXAMPLE_REDIS') ?: 'localhost']);
        $r->flushAll();

        return $r;
    }
}
