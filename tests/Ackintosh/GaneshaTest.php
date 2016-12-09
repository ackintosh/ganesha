<?php
namespace Ackintosh;

class GaneshaTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function recordsFailureAndTrips()
    {
        $ganesha = new Ganesha(2);
        $this->assertTrue($ganesha->isAvailable());

        $ganesha->recordFailure();
        $ganesha->recordFailure();
        $this->assertFalse($ganesha->isAvailable());
    }

    /**
     * @test
     */
    public function recordsSuccessAndClose()
    {
        $ganesha = new Ganesha(2);
        $ganesha->recordFailure();
        $ganesha->recordFailure();
        $this->assertFalse($ganesha->isAvailable());

        $ganesha->recordSuccess();
        $this->assertTrue($ganesha->isAvailable());
    }
}
