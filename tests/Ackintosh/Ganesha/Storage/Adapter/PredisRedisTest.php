<?php

namespace Ackintosh\Ganesha\Storage\Adapter;

use Predis\Client;
use Predis\ClientInterface;

class PredisRedisTest extends AbstractRedisTest
{
    protected function getRedisConnection(): ClientInterface
    {
        $r = new Client('tcp://' . (getenv('GANESHA_EXAMPLE_REDIS') ?: 'localhost'));
        $r->connect();
        $r->flushall();

        return $r;
    }
}
