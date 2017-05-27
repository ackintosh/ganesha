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
            ->with(Ganesha::EVENT_TRIPPED, $this->serviceName, '');

        $ganesha->subscribe(function ($event, $serviceName, $message) use ($receiver) {
            $receiver->receive($event, $serviceName, $message);
        });

        $ganesha->failure($this->serviceName);
        $ganesha->failure($this->serviceName);
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
