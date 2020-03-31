<?php

namespace Ackintosh\Ganesha\Storage\Adapter;

use Predis\Client;

class PredisRedisTest extends AbstractRedisTest
{
    /**
     * @return Client
     */
    protected function getRedisConnection(): Client
    {
        $r = new Client('tcp://' . (getenv('GANESHA_EXAMPLE_REDIS') ?: 'localhost'));
        $r->connect();
        $r->flushall();

        return $r;
    }
}
