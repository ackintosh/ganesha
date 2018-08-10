<?php

namespace Ackintosh\Ganesha\Storage\Adapter;

class PredisRedisTest extends AbstractRedisTest
{
    /**
     * @return \Predis\Client
     */
    protected function getRedisConnection()
    {
        $r = new \Predis\Client(getenv('GANESHA_EXAMPLE_REDIS') ?: 'localhost');
        $r->flushAll();

        return $r;
    }
}
