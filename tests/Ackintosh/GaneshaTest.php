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
        $ganesha = $this->buildGaneshaWithMemcachedAdapter(2);
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
        $ganesha = $this->buildGaneshaWithMemcachedAdapter(2);
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

        $ganesha = Builder::buildWithCountStrategy(array(
            'failureThreshold'  => 2,
            'adapter'           => new Hash,
            'behaviorOnTrip'    => function ($serviceName) use ($mock) {
                $mock->foo($serviceName);
            },
        ));

        $ganesha->failure($this->serviceName);
        $ganesha->failure($this->serviceName);
    }

    /**
     * @test
     */
    public function onTripBehaviorIsInvokedUnderCertainConditions()
    {
        $invoked = 0;
        $ganesha = Builder::buildWithCountStrategy(array(
            'failureThreshold'  => 2,
            'adapter'           => new Hash,
            'behaviorOnTrip'    => function ($serviceName) use (&$invoked) {
                $invoked++;
            },
        ));

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
            'failureThreshold'      => 1,
            'adapterSetupFunction'  => function () {
                $m = new \Memcached();
                $m->addServer('localhost', 11211);

                return new Memcached($m);
            },
        ));

        $this->assertTrue($ganesha->isAvailable($this->serviceName));
        $ganesha->failure($this->serviceName);
        $this->assertFalse($ganesha->isAvailable($this->serviceName));
    }

    /**
     * @test
     */
    public function withMemcachedTTL()
    {
        $ganesha = Builder::buildWithCountStrategy(array(
            'failureThreshold'      => 1,
            'countTTL'              => 1,
            'adapterSetupFunction'  => function () {
                $m = new \Memcached();
                $m->addServer('localhost', 11211);

                return new Memcached($m);
            },
        ));

        $ganesha->failure($this->serviceName);
        $this->assertFalse($ganesha->isAvailable($this->serviceName));
        sleep(1);
        $this->assertTrue($ganesha->isAvailable($this->serviceName));
    }

    /**
     * @test
     */
    public function failureCountMustNotBeNegative()
    {
        $ganesha = Builder::buildWithCountStrategy(array(
            'failureThreshold'  => 1,
            'adapter'           => new Hash,
        ));

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
        $ganesha = Builder::buildWithCountStrategy(array(
            'failureThreshold'      => 1,
            'adapter'               => new Hash,
            'countTTL'              => 60,
            'intervalToHalfOpen'    => 1,
        ));

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
    public function status()
    {
        $m = new \Memcached();
        $m->addServer('localhost', 11211);
        $memcachedAdapter = new Memcached($m);

        $ganesha = Builder::buildWithCountStrategy(array(
            'failureThreshold'      => 2,
            'adapterSetupFunction'  => function () use ($memcachedAdapter) {
                return $memcachedAdapter;
            },
        ));

        $ganesha->failure($this->serviceName);
        $this->assertSame(Ganesha::STATUS_CALMED_DOWN, $memcachedAdapter->loadStatus($this->serviceName));
        // trip
        $ganesha->failure($this->serviceName);
        $this->assertSame(Ganesha::STATUS_TRIPPED, $memcachedAdapter->loadStatus($this->serviceName));
        // service is available, but status is still OPEN
        $ganesha->success($this->serviceName);
        $this->assertSame(Ganesha::STATUS_TRIPPED, $memcachedAdapter->loadStatus($this->serviceName));
        // failure count is 0, status changes to CLOSE
        $ganesha->success($this->serviceName);
        $this->assertSame(Ganesha::STATUS_CALMED_DOWN, $memcachedAdapter->loadStatus($this->serviceName));
    }

    /**
     * @test
     */
    public function disable()
    {
        $ganesha = $this->buildGaneshaWithMemcachedAdapter(1);

        $ganesha->failure($this->serviceName);
        $this->assertFalse($ganesha->isAvailable($this->serviceName));

        Ganesha::disable();
        $this->assertTrue($ganesha->isAvailable($this->serviceName));

        Ganesha::enable();
        $this->assertFalse($ganesha->isAvailable($this->serviceName));
    }

    private function buildGaneshaWithMemcachedAdapter($threshold)
    {
        return Builder::buildWithCountStrategy(array(
            'failureThreshold'  => $threshold,
            'adapterSetupFunction' => function () {
                $m = new \Memcached();
                $m->addServer('localhost', 11211);

                return new \Ackintosh\Ganesha\Storage\Adapter\Memcached($m);
            },
        ));
    }

    /**
     * @test
     */
    public function withRateStrategy()
    {
        $ganesha = Builder::build(array(
            'adapterSetupFunction' => function () {
                $m = new \Memcached();
                $m->addServer('localhost', 11211);

                return new \Ackintosh\Ganesha\Storage\Adapter\Memcached($m);
            },
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
}
