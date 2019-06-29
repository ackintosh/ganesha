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
     * @expectedException \InvalidArgumentException
     */
    public function validateThrowsExceptionWhenTheAdapterDoesntSupportCountStrategy()
    {
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

        Count::validate($params);
    }
}
