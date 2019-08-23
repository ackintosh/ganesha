<?php
namespace Ackintosh\Ganesha;

use Ackintosh\Ganesha\Storage\Adapter\Memcached;
use Ackintosh\Ganesha\Strategy\Count;

class CountTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @expectedException \LogicException
     */
    public function validateThrowsExceptionWhenTheRequiredParamsIsMissing()
    {
        Count::validate([]);
    }

    /**
     * @test
     */
    public function validateThrowsExceptionWhenTheAdapterDoesntSupportCountStrategy()
    {
        if (!\extension_loaded('memcached')) {
            self::markTestSkipped('No ext-memcached present');
        }

        $adapter = $this->getMockBuilder(Memcached::class)
            ->setConstructorArgs([new \Memcached()])
            ->getMock();
        $adapter->method('supportCountStrategy')
            ->willReturn(false);

        $params = [
            'adapter' => $adapter,
            'failureCountThreshold' => 10,
            'intervalToHalfOpen' => 10,
        ];

        $this->expectException(\InvalidArgumentException::class);
        Count::validate($params);
    }
}
