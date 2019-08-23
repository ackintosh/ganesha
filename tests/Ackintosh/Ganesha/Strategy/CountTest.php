<?php
namespace Ackintosh\Ganesha;

use Ackintosh\Ganesha\Storage\AdapterInterface;
use Ackintosh\Ganesha\Strategy\Count;

/**
 * @coversDefaultClass \Ackintosh\Ganesha\Strategy\Count
 */
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
        $adapter = $this->createMock(AdapterInterface::class);
        $adapter->expects(self::atLeastOnce())
            ->method('supportCountStrategy')
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
