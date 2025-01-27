<?php

namespace Ackintosh\Ganesha\Storage\Adapter;

use Predis\ClientInterface;

class PredisRedisClientInterfaceTest extends PredisRedisTest
{
    protected function getRedisConnection(): ClientInterface
    {
        return new PredisRedisClientInterfaceTestDouble(
            parent::getRedisConnection()
        );
    }
}
