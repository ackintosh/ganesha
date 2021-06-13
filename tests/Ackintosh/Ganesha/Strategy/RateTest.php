<?php
namespace Ackintosh\Ganesha\Strategy;

use Ackintosh\Ganesha\Storage\AdapterInterface;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Ackintosh\Ganesha\Strategy\Rate
 */
class RateTest extends TestCase
{
    /**
     * @test
     */
    public function validateThrowsExceptionWhenTheRequiredParamsIsMissing()
    {
        $this->expectException(\LogicException::class);
        Rate::validate([]);
    }

    /**
     * @test
     */
    public function validateThrowsExceptionWhenTheAdapterDoesntSupportCountStrategy()
    {
        $adapter = $this->createMock(AdapterInterface::class);
        $adapter->expects(self::atLeastOnce())
            ->method('supportRateStrategy')
            ->willReturn(false);

        $params = [
            'adapter' => $adapter,
            'failureRateThreshold' => 10,
            'intervalToHalfOpen' => 10,
            'minimumRequests' => 10,
            'timeWindow' => 10,
        ];

        $this->expectException(\InvalidArgumentException::class);
        Rate::validate($params);
    }
}
