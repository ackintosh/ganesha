<?php
namespace Ackintosh;

class GaneshaTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function recordsFailureAndTrips()
    {
        $serviceName = 'test';
        $ganesha = new Ganesha(2);
        $this->assertTrue($ganesha->isAvailable($serviceName));

        $ganesha->recordFailure($serviceName);
        $ganesha->recordFailure($serviceName);
        $this->assertFalse($ganesha->isAvailable($serviceName));
    }

    /**
     * @test
     */
    public function recordsSuccessAndClose()
    {
        $ganesha = new Ganesha(2);
        $serviceName = 'test';
        $ganesha->recordFailure($serviceName);
        $ganesha->recordFailure($serviceName);
        $this->assertFalse($ganesha->isAvailable($serviceName));

        $ganesha->recordSuccess($serviceName);
        $this->assertTrue($ganesha->isAvailable($serviceName));
    }
}
