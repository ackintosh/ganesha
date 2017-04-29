<?php
namespace Ackintosh;

use Ackintosh\Ganesha\Builder;
use Ackintosh\Ganesha\Storage\Adapter\Memcached;

class GaneshaTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    private $serviceName = 'GaneshaTestService';

    /**
     * @var \Memcached
     */
    private $m;

    public function setUp()
    {
        parent::setUp();
        $this->m = new \Memcached();
        $this->m->addServer('localhost', 11211);
        $this->m->flush();
    }

    /**
     * @test
     */
    public function recordsFailureAndTrips()
    {
        $ganesha = $this->buildGanesha(2);
        $this->assertTrue($ganesha->isAvailable($this->serviceName));

        $ganesha->failure($this->serviceName);
        $ganesha->failure($this->serviceName);
        $this->assertFalse($ganesha->isAvailable($this->serviceName));
        // it does not affect other services.
        $this->assertTrue($ganesha->isAvailable('other' . $this->serviceName));
    }

    /**
     * @test
     */
    public function recordsSuccessAndClose()
    {
        $ganesha = $this->buildGanesha(2);
        $ganesha->failure($this->serviceName);
        $ganesha->failure($this->serviceName);
        $this->assertFalse($ganesha->isAvailable($this->serviceName));

        $ganesha->success($this->serviceName);
        $this->assertTrue($ganesha->isAvailable($this->serviceName));
    }

    /**
     * @test
     */
    public function onTripInvokesItsBehaviorWhenGaneshaHasTripped()
    {
        $mock = $this->getMockBuilder('\stdClass')
            ->setMethods(array('foo'))
            ->getMock();
        $mock->expects($this->once())
            ->method('foo')
            ->with($this->serviceName);

        $ganesha = $this->buildGanesha(
            2,
            10,
            function ($serviceName) use ($mock) {
                $mock->foo($serviceName);
            }
        );

        $ganesha->failure($this->serviceName);
        $ganesha->failure($this->serviceName);
    }

    /**
     * @test
     */
    public function onTripBehaviorIsInvokedUnderCertainConditions()
    {
        $invoked = 0;
        $ganesha = $this->buildGanesha(
            2,
            10,
            function ($serviceName) use (&$invoked) {
                $invoked++;
            }
        );

        // tipped and incremented $invoked.
        $ganesha->failure($this->serviceName);
        $ganesha->failure($this->serviceName);
        $this->assertSame(1, $invoked);

        // closed.
        $ganesha->success($this->serviceName);

        // tripped again, but $invoke is not incremented.
        $ganesha->failure($this->serviceName);
        $this->assertSame(1, $invoked);

        // calm down ( failure count = 0 )
        $ganesha->success($this->serviceName);
        $ganesha->success($this->serviceName);

        // tripped and incremented $invoked.
        $ganesha->failure($this->serviceName);
        $ganesha->failure($this->serviceName);
        $this->assertSame(2, $invoked);
    }

    /**
     * @test
     */
    public function withMemcached()
    {
        $ganesha = Builder::buildWithCountStrategy(array(
            'failureThreshold'  => 1,
            'adapter'           => new Memcached($this->m),
            'intervalToHalfOpen'=> 10,
        ));

        $this->assertTrue($ganesha->isAvailable($this->serviceName));
        $ganesha->failure($this->serviceName);
        $this->assertFalse($ganesha->isAvailable($this->serviceName));
    }

    /**
     * @test
     */
    public function failureCountMustNotBeNegative()
    {
        $ganesha = $this->buildGanesha(1);

        $ganesha->success($this->serviceName);
        $ganesha->success($this->serviceName);
        $ganesha->success($this->serviceName);
        $this->assertTrue($ganesha->isAvailable($this->serviceName));

        $ganesha->failure($this->serviceName);
        $this->assertFalse($ganesha->isAvailable($this->serviceName));
    }

    /**
     * @test
     */
    public function withIntervalToHalfOpen()
    {
        $ganesha = $this->buildGanesha(
            1,
            1
        );

        $this->assertTrue($ganesha->isAvailable($this->serviceName));
        // record a failure, ganesha has trip
        $ganesha->failure($this->serviceName);
        $this->assertFalse($ganesha->isAvailable($this->serviceName));
        // wait for the interval to half-open
        sleep(2);
        // half-open
        $this->assertTrue($ganesha->isAvailable($this->serviceName));
        // after half-open, service is not available until the interval has elapsed
        $this->assertFalse($ganesha->isAvailable($this->serviceName));
        // record a success, ganesha has close
        $ganesha->success($this->serviceName);
        $this->assertTrue($ganesha->isAvailable($this->serviceName));
    }

    /**
     * @test
     */
    public function disable()
    {
        $ganesha = $this->buildGanesha(1);

        $ganesha->failure($this->serviceName);
        $this->assertFalse($ganesha->isAvailable($this->serviceName));

        Ganesha::disable();
        $this->assertTrue($ganesha->isAvailable($this->serviceName));

        Ganesha::enable();
        $this->assertFalse($ganesha->isAvailable($this->serviceName));
    }

    /**
     * @test
     */
    public function reset()
    {
        $ganesha = $this->buildGanesha(1);

        $ganesha->failure($this->serviceName);
        $this->assertFalse($ganesha->isAvailable($this->serviceName));
        $ganesha->reset();
        $this->assertTrue($ganesha->isAvailable($this->serviceName));
    }

    /**
     * @test
     */
    public function withRateStrategy()
    {
        $ganesha = Builder::build(array(
            'adapter' => new Memcached($this->m),
            'timeWindow' => 3,
            'failureRate' => 50,
            'minimumRequests' => 1,
            'intervalToHalfOpen' => 10,
        ));

        $this->assertTrue($ganesha->isAvailable('test'));

        $ganesha->failure('test');
        $ganesha->failure('test');
        $ganesha->failure('test');
        $ganesha->success('test');
        $ganesha->success('test');

        $this->assertFalse($ganesha->isAvailable('test'));
    }

    private function buildGanesha(
        $threshold,
        $intervalToHalfOpen = 10,
        $onTrip = null
    )
    {
        return Builder::buildWithCountStrategy(array(
            'failureThreshold'      => $threshold,
            'adapter'               => new Memcached($this->m),
            'onTrip'                => $onTrip,
            'intervalToHalfOpen'    => $intervalToHalfOpen,
        ));
    }
}
