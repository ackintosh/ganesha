<?php
namespace Ackintosh;

use Ackintosh\Ganesha\Builder;
use Ackintosh\Ganesha\Storage\Adapter\Memcached;
use PHPUnit\Framework\TestCase;

class GaneshaTest extends TestCase
{
    /**
     * @var string
     */
    private $service = 'GaneshaTestService';

    /**
     * @var \Memcached
     */
    private $m;

    protected function setUp(): void
    {
        parent::setUp();

        if (!\extension_loaded('memcached')) {
            self::markTestSkipped('No ext-memcached present');
        }

        $this->m = new \Memcached();
        $this->m->addServer(
            getenv('GANESHA_EXAMPLE_MEMCACHED') ? getenv('GANESHA_EXAMPLE_MEMCACHED') : 'localhost',
            11211
        );
        $this->m->flush();
    }

    /**
     * @test
     */
    public function recordsFailureAndTrips()
    {
        $ganesha = $this->buildGanesha(2);
        $this->assertTrue($ganesha->isAvailable($this->service));

        $ganesha->failure($this->service);
        $ganesha->failure($this->service);
        $this->assertFalse($ganesha->isAvailable($this->service));
        // it does not affect other services.
        $this->assertTrue($ganesha->isAvailable('other' . $this->service));
    }

    /**
     * @test
     */
    public function recordsSuccessAndClose()
    {
        $ganesha = $this->buildGanesha(2);
        $ganesha->failure($this->service);
        $ganesha->failure($this->service);
        $this->assertFalse($ganesha->isAvailable($this->service));

        $ganesha->success($this->service);
        $this->assertTrue($ganesha->isAvailable($this->service));
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
            ->with(Ganesha::EVENT_TRIPPED, $this->service, '');

        $ganesha->subscribe(function ($event, $service, $message) use ($receiver) {
            $receiver->receive($event, $service, $message);
        });

        $ganesha->failure($this->service);
        $ganesha->failure($this->service);
    }


    /**
     * @test
     */
    public function withMemcached()
    {
        $ganesha = Builder::withCountStrategy()
            ->failureCountThreshold(1)
            ->adapter(new Memcached($this->m))
            ->intervalToHalfOpen(10)
            ->build();

        $this->assertTrue($ganesha->isAvailable($this->service));
        $ganesha->failure($this->service);
        $this->assertFalse($ganesha->isAvailable($this->service));
    }

    /**
     * @test
     */
    public function failureCountMustNotBeNegative()
    {
        $ganesha = $this->buildGanesha(1);

        $ganesha->success($this->service);
        $ganesha->success($this->service);
        $ganesha->success($this->service);
        $this->assertTrue($ganesha->isAvailable($this->service));

        $ganesha->failure($this->service);
        $this->assertFalse($ganesha->isAvailable($this->service));
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

        $this->assertTrue($ganesha->isAvailable($this->service));
        // record a failure, ganesha has trip
        $ganesha->failure($this->service);
        $this->assertFalse($ganesha->isAvailable($this->service));
        // wait for the interval to half-open
        sleep(2);
        // half-open
        $this->assertTrue($ganesha->isAvailable($this->service));
        // after half-open, service is not available until the interval has elapsed
        $this->assertFalse($ganesha->isAvailable($this->service));
        // record a success, ganesha has close
        $ganesha->success($this->service);
        $this->assertTrue($ganesha->isAvailable($this->service));
    }

    /**
     * @test
     */
    public function disable()
    {
        $ganesha = $this->buildGanesha(1);

        $ganesha->failure($this->service);
        $this->assertFalse($ganesha->isAvailable($this->service));

        Ganesha::disable();
        $this->assertTrue($ganesha->isAvailable($this->service));

        Ganesha::enable();
        $this->assertFalse($ganesha->isAvailable($this->service));
    }

    /**
     * @test
     * @requires PHP 7.0
     */
    public function reset()
    {
        $ganesha = $this->buildGanesha(1);

        $ganesha->failure($this->service);
        $this->assertFalse($ganesha->isAvailable($this->service));

        // For making sure that \Memcached::getAllKeys() (be called by Ganesha::reset()) takes ALL keys, we need to wait a moment...
        sleep(1);

        $ganesha->reset();
        $this->assertTrue($ganesha->isAvailable($this->service));
    }

    /**
     * @test
     */
    public function withRateStrategy()
    {
        $ganesha = Builder::withRateStrategy()
            ->adapter(new Memcached($this->m))
            ->timeWindow(3)
            ->failureRateThreshold(50)
            ->minimumRequests(1)
            ->intervalToHalfOpen(10)
            ->build();

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
    ) {
        return Builder::withCountStrategy()
            ->failureCountThreshold($threshold)
            ->adapter(new Memcached($this->m))
            ->intervalToHalfOpen($intervalToHalfOpen)
            ->build();
    }
}
