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
        $this->assertTrue($ganesha->isClosed());

        $ganesha->recordFailure();
        $ganesha->recordFailure();
        $this->assertFalse($ganesha->isClosed());
    }

    /**
     * @test
     */
    public function recordsSuccessAndClose()
    {
        $ganesha = new Ganesha(2);
        $ganesha->recordFailure();
        $ganesha->recordFailure();
        $this->assertFalse($ganesha->isClosed());

        $ganesha->recordSuccess();
        $this->assertTrue($ganesha->isClosed());
    }
}
