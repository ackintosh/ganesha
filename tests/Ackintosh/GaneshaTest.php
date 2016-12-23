<?php
namespace Ackintosh;

use Ackintosh\Ganesha\Builder;
use Ackintosh\Ganesha\Storage;
use Ackintosh\Ganesha\Storage\Adapter\Hash;
use Ackintosh\Ganesha\Storage\Adapter\Memcached;

class GaneshaTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function recordsFailureAndTrips()
    {
        $serviceName = 'test';
        $ganesha = $this->buildGaneshaWithHashAdapter(2);
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
        $ganesha = $this->buildGaneshaWithHashAdapter(2);
        $serviceName = 'test';
        $ganesha->recordFailure($serviceName);
        $ganesha->recordFailure($serviceName);
        $this->assertFalse($ganesha->isAvailable($serviceName));

        $ganesha->recordSuccess($serviceName);
        $this->assertTrue($ganesha->isAvailable($serviceName));
    }

    /**
     * @test
     */
    public function invokesItsBehaviorWhenGaneshaHasTripped()
    {
        $ganesha = $this->buildGaneshaWithHashAdapter(2);
        $serviceName = 'test';

        $mock = $this->getMockBuilder(\stdClass::class)
            ->setMethods(['foo'])
            ->getMock();
        $mock->expects($this->once())
            ->method('foo')
            ->with($serviceName);

        $ganesha->onTrip(function ($serviceName) use ($mock) {
            $mock->foo($serviceName);
        });

        $ganesha->recordFailure($serviceName);
        $ganesha->recordFailure($serviceName);
    }

    /**
     * @test
     */
    public function withMemcached()
    {
        $ganesha = Builder::create()
            ->withFailureThreshold(1)
            ->withStorageSetupFunction(function () {
                $m = new \Memcached();
                $m->addServer('localhost', 11211);

                return new Storage(new Memcached($m));
            })
            ->build();

        $serviceName = 'test';
        $this->assertTrue($ganesha->isAvailable($serviceName));
        $ganesha->recordFailure($serviceName);
        $this->assertFalse($ganesha->isAvailable($serviceName));
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function onTripThrowsException()
    {
        $ganesha = $this->buildGaneshaWithHashAdapter(2);
        $ganesha->onTrip(1);
    }

    private function buildGaneshaWithHashAdapter($threshold)
    {
        return Builder::create()
            ->withFailureThreshold($threshold)
            ->withStorageAdapter(new Hash)
            ->build();
    }
}
