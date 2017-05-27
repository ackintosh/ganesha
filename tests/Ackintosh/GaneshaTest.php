<?php
namespace Ackintosh;

use Ackintosh\Ganesha\Builder;
use Ackintosh\Ganesha\Storage\Adapter\Memcached;

class GaneshaTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    private $resource = 'GaneshaTestService';

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
        $this->assertTrue($ganesha->isAvailable($this->resource));

        $ganesha->failure($this->resource);
        $ganesha->failure($this->resource);
        $this->assertFalse($ganesha->isAvailable($this->resource));
        // it does not affect other services.
        $this->assertTrue($ganesha->isAvailable('other' . $this->resource));
    }

    /**
     * @test
     */
    public function recordsSuccessAndClose()
    {
        $ganesha = $this->buildGanesha(2);
        $ganesha->failure($this->resource);
        $ganesha->failure($this->resource);
        $this->assertFalse($ganesha->isAvailable($this->resource));

        $ganesha->success($this->resource);
        $this->assertTrue($ganesha->isAvailable($this->resource));
    }

    /**
     * @test
     */
    public function notifyTripped()
    {
        $ganesha = $this->buildGanesha(
            2,
            10
        );

        $receiver = $this->getMockBuilder('\stdClass')
            ->setMethods(['receive'])
            ->getMock();
        $receiver->expects($this->once())
            ->method('receive')
            ->with(Ganesha::EVENT_TRIPPED, $this->resource, '');

        $ganesha->subscribe(function ($event, $resource, $message) use ($receiver) {
            $receiver->receive($event, $resource, $message);
        });

        $ganesha->failure($this->resource);
        $ganesha->failure($this->resource);
    }


    /**
     * @test
     */
    public function withMemcached()
    {
        $ganesha = Builder::buildWithCountStrategy([
            'failureThreshold'  => 1,
            'adapter'           => new Memcached($this->m),
            'intervalToHalfOpen'=> 10,
        ]);

        $this->assertTrue($ganesha->isAvailable($this->resource));
        $ganesha->failure($this->resource);
        $this->assertFalse($ganesha->isAvailable($this->resource));
    }

    /**
     * @test
     */
    public function failureCountMustNotBeNegative()
    {
        $ganesha = $this->buildGanesha(1);

        $ganesha->success($this->resource);
        $ganesha->success($this->resource);
        $ganesha->success($this->resource);
        $this->assertTrue($ganesha->isAvailable($this->resource));

        $ganesha->failure($this->resource);
        $this->assertFalse($ganesha->isAvailable($this->resource));
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

        $this->assertTrue($ganesha->isAvailable($this->resource));
        // record a failure, ganesha has trip
        $ganesha->failure($this->resource);
        $this->assertFalse($ganesha->isAvailable($this->resource));
        // wait for the interval to half-open
        sleep(2);
        // half-open
        $this->assertTrue($ganesha->isAvailable($this->resource));
        // after half-open, service is not available until the interval has elapsed
        $this->assertFalse($ganesha->isAvailable($this->resource));
        // record a success, ganesha has close
        $ganesha->success($this->resource);
        $this->assertTrue($ganesha->isAvailable($this->resource));
    }

    /**
     * @test
     */
    public function disable()
    {
        $ganesha = $this->buildGanesha(1);

        $ganesha->failure($this->resource);
        $this->assertFalse($ganesha->isAvailable($this->resource));

        Ganesha::disable();
        $this->assertTrue($ganesha->isAvailable($this->resource));

        Ganesha::enable();
        $this->assertFalse($ganesha->isAvailable($this->resource));
    }

    /**
     * @test
     */
    public function reset()
    {
        $ganesha = $this->buildGanesha(1);

        $ganesha->failure($this->resource);
        $this->assertFalse($ganesha->isAvailable($this->resource));
        $ganesha->reset();
        $this->assertTrue($ganesha->isAvailable($this->resource));
    }

    /**
     * @test
     */
    public function withRateStrategy()
    {
        $ganesha = Builder::build([
            'adapter' => new Memcached($this->m),
            'timeWindow' => 3,
            'failureRate' => 50,
            'minimumRequests' => 1,
            'intervalToHalfOpen' => 10,
        ]);

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
        $intervalToHalfOpen = 10
    )
    {
        return Builder::buildWithCountStrategy([
            'failureThreshold'      => $threshold,
            'adapter'               => new Memcached($this->m),
            'intervalToHalfOpen'    => $intervalToHalfOpen,
        ]);
    }
}
