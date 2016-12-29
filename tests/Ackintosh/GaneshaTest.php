<?php
namespace Ackintosh;

use Ackintosh\Ganesha\Builder;
use Ackintosh\Ganesha\Storage\Adapter\Hash;
use Ackintosh\Ganesha\Storage\Adapter\Memcached;

class GaneshaTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    private $serviceName = 'GaneshaTestService';

    public function setUp()
    {
        parent::setUp();
        $m = new \Memcached();
        $m->addServer('localhost', 11211);
        $m->flush();
    }

    /**
     * @test
     */
    public function recordsFailureAndTrips()
    {
        $ganesha = $this->buildGaneshaWithHashAdapter(2);
        $this->assertTrue($ganesha->isAvailable($this->serviceName));

        $ganesha->recordFailure($this->serviceName);
        $ganesha->recordFailure($this->serviceName);
        $this->assertFalse($ganesha->isAvailable($this->serviceName));
    }

    /**
     * @test
     */
    public function recordsSuccessAndClose()
    {
        $ganesha = $this->buildGaneshaWithHashAdapter(2);
        $ganesha->recordFailure($this->serviceName);
        $ganesha->recordFailure($this->serviceName);
        $this->assertFalse($ganesha->isAvailable($this->serviceName));

        $ganesha->recordSuccess($this->serviceName);
        $this->assertTrue($ganesha->isAvailable($this->serviceName));
    }

    /**
     * @test
     */
    public function invokesItsBehaviorWhenGaneshaHasTripped()
    {
        $ganesha = $this->buildGaneshaWithHashAdapter(2);

        $mock = $this->getMockBuilder(\stdClass::class)
            ->setMethods(['foo'])
            ->getMock();
        $mock->expects($this->once())
            ->method('foo')
            ->with($this->serviceName);

        $ganesha->onTrip(function ($serviceName) use ($mock) {
            $mock->foo($serviceName);
        });

        $ganesha->recordFailure($this->serviceName);
        $ganesha->recordFailure($this->serviceName);
    }

    /**
     * @test
     */
    public function withMemcached()
    {
        $ganesha = Builder::create()
            ->withFailureThreshold(1)
            ->withAdapterSetupFunction(function () {
                $m = new \Memcached();
                $m->addServer('localhost', 11211);

                return new Memcached($m);
            })
            ->build();

        $this->assertTrue($ganesha->isAvailable($this->serviceName));
        $ganesha->recordFailure($this->serviceName);
        $this->assertFalse($ganesha->isAvailable($this->serviceName));
    }

    /**
     * @test
     */
    public function withMemcachedTTL()
    {
        $ganesha = Builder::create()
            ->withFailureThreshold(1)
            ->withAdapterSetupFunction(function () {
                $m = new \Memcached();
                $m->addServer('localhost', 11211);

                return new Memcached($m);
            })
            ->withCountTTL(1)
            ->build();

        $ganesha->recordFailure($this->serviceName);
        $this->assertFalse($ganesha->isAvailable($this->serviceName));
        sleep(1);
        $this->assertTrue($ganesha->isAvailable($this->serviceName));
    }

    /**
     * @test
     */
    public function failureCountMustNotBeNegative()
    {
        $ganesha = Builder::create()
            ->withFailureThreshold(1)
            ->withAdapter(new Hash())
            ->build();

        $ganesha->recordSuccess($this->serviceName);
        $ganesha->recordSuccess($this->serviceName);
        $ganesha->recordSuccess($this->serviceName);
        $this->assertTrue($ganesha->isAvailable($this->serviceName));

        $ganesha->recordFailure($this->serviceName);
        $this->assertFalse($ganesha->isAvailable($this->serviceName));
    }

    /**
     * @test
     */
    public function withIntervalToHalfOpen()
    {
        $ganesha = Builder::create()
            ->withAdapter(new Hash())
            ->withFailureThreshold(1)
            ->withIntervalToHalfOpen(1)
            ->withCountTTL(60)
            ->build();

        $this->assertTrue($ganesha->isAvailable($this->serviceName));
        // record a failure, ganesha has trip
        $ganesha->recordFailure($this->serviceName);
        $this->assertFalse($ganesha->isAvailable($this->serviceName));
        // wait for the interval to half-open
        sleep(2);
        // half-open
        $this->assertTrue($ganesha->isAvailable($this->serviceName));
        // after half-open, service is not available until the interval has elapsed
        $this->assertFalse($ganesha->isAvailable($this->serviceName));
        // record a success, ganesha has close
        $ganesha->recordSuccess($this->serviceName);
        $this->assertTrue($ganesha->isAvailable($this->serviceName));
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

    /**
     * @test
     */
    public function status()
    {
        $m = new \Memcached();
        $m->addServer('localhost', 11211);
        $memcachedAdapter = new Memcached($m);

        $ganesha = Builder::create()
            ->withFailureThreshold(2)
            ->withAdapterSetupFunction(function () use ($memcachedAdapter) {
                return $memcachedAdapter;
            })
            ->build();

        $ganesha->recordFailure($this->serviceName);
        $this->assertSame(Ganesha::STATUS_CLOSE, $memcachedAdapter->loadStatus($this->serviceName));
        // trip
        $ganesha->recordFailure($this->serviceName);
        $this->assertSame(Ganesha::STATUS_OPEN, $memcachedAdapter->loadStatus($this->serviceName));
        // service is available, but status is still OPEN
        $ganesha->recordSuccess($this->serviceName);
        $this->assertSame(Ganesha::STATUS_OPEN, $memcachedAdapter->loadStatus($this->serviceName));
        // failure count is 0, status changes to CLOSE
        $ganesha->recordSuccess($this->serviceName);
        $this->assertSame(Ganesha::STATUS_CLOSE, $memcachedAdapter->loadStatus($this->serviceName));
    }

    private function buildGaneshaWithHashAdapter($threshold)
    {
        return Builder::create()
            ->withFailureThreshold($threshold)
            ->withAdapter(new Hash)
            ->build();
    }
}
