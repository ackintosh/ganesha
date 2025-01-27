<?php

namespace Ackintosh\Ganesha\Storage\Adapter;

use Predis\ClientInterface;

class PredisRedisClientInterfaceTest extends PredisRedisSpec
{
    protected function getRedisConnection(): ClientInterface
    {
        return new PredisRedisClientInterfaceTestDouble(
            parent::getRedisConnection()
        );
    }
}
