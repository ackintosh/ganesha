<?php

namespace Ackintosh\Ganesha\Storage\Adapter;

use PHPUnit\Framework\TestCase;

class RedisStoreTest extends TestCase
{
    /**
     * @test
     */
    public function constructorThrowsExceptionIfTheClientIsNotSupported()
    {
        $this->expectException(\InvalidArgumentException::class);

        new RedisStore(new \stdClass());
    }

    /**
     * @test
     */
    public function zCardThrowsExceptionIfFailed()
    {
        $this->expectException(\Ackintosh\Ganesha\Exception\StorageException::class);

        $mock = $this->getMockBuilder(\Redis::class)
            ->onlyMethods(['zCard'])
            ->getMock();
        $mock->expects($this->any())
            ->method('zCard')
            ->willThrowException(new \RuntimeException());

        (new RedisStore($mock))->zCard("test key");
    }
}
