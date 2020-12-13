<?php
namespace Ackintosh\Ganesha\Storage\Adapter;

use Ackintosh\Ganesha;
use Ackintosh\Ganesha\Exception\StorageException;
use PHPUnit\Framework\TestCase;
use function extension_loaded;

/**
 * @coversDefaultClass \Ackintosh\Ganesha\Storage\Adapter\Memcached
 */
class MemcachedTest extends TestCase
{
    /**
     * @var Memcached
     */
    private $memcachedAdaper;

    /**
     * @var \Memcached
     */
    private $memcached;

    /**
     * @var string
     */
    private $service = 'testService';

    protected function setUp()
    {
        if (!extension_loaded('memcached')) {
            self::markTestSkipped('No ext-memcached present');
        }

        $m = new \Memcached();
        $m->addServer(
            getenv('GANESHA_EXAMPLE_MEMCACHED') ? getenv('GANESHA_EXAMPLE_MEMCACHED') : 'localhost',
            11211
        );
        $m->delete($this->service);
        $this->memcached = $m;
        $this->memcachedAdaper = new Memcached($m);
        $this->memcachedAdaper->setContext($this->createContext($this->memcachedAdaper));
    }

    private function createContext(Memcached $memcachedAdaper): Ganesha\Context
    {
        return new Ganesha\Context(
            Ganesha\Strategy\Rate::class,
            $memcachedAdaper,
            new Ganesha\Configuration([
                Ganesha\Configuration::TIME_WINDOW => 1,
            ])
        );
    }

    /**
     * @test
     * @covers ::supportCountStrategy
     */
    public function supportsCountStrategy()
    {
        $this->assertTrue($this->memcachedAdaper->supportCountStrategy());
    }

    /**
     * @test
     * @covers ::supportRateStrategy
     */
    public function supportsRateStrategy()
    {
        $this->assertTrue($this->memcachedAdaper->supportRateStrategy());
    }

    /**
     * @test
     * @covers ::load
     */
    public function saveAndLoad()
    {
        $this->memcachedAdaper->save($this->service, 1);
        $this->assertSame(1, $this->memcachedAdaper->load($this->service));
    }

    /**
     * @test
     * @covers ::load
     */
    public function loadThrowsException()
    {
        $m = $this->getMockBuilder(\Memcached::class)
            ->setMethods(['getResultCode'])
            ->getMock();
        $m->expects($this->once())
            ->method('getResultCode')
            ->willReturn(\Memcached::RES_FAILURE);

        $adapter = new Memcached($m);

        $this->expectException(StorageException::class);
        $adapter->load($this->service);
    }

    /**
     * @test
     * @covers ::save
     */
    public function saveThrowsException()
    {
        $m = $this->getMockBuilder(\Memcached::class)
            ->setMethods(['set'])
            ->getMock();
        $m->expects($this->once())
            ->method('set')
            ->willReturn(false);

        $adapter = new Memcached($m);

        $this->expectException(StorageException::class);
        $adapter->save($this->service, 99);
    }

    /**
     * @test
     * @covers ::load
     * @covers ::increment
     */
    public function increment()
    {
        $this->memcachedAdaper->increment($this->service);
        $this->assertSame(1, $this->memcachedAdaper->load($this->service));
        $this->memcachedAdaper->increment($this->service);
        $this->assertSame(2, $this->memcachedAdaper->load($this->service));
    }

    /**
     * @test
     * @covers ::increment
     */
    public function incrementThrowsException()
    {
        $m = $this->getMockBuilder(\Memcached::class)
            ->setMethods(['increment'])
            ->getMock();
        $m->expects($this->once())
            ->method('increment')
            ->willReturn(false);

        $adapter = new Memcached($m);
        $adapter->setContext($this->createContext($adapter));

        $this->expectException(StorageException::class);
        $adapter->increment($this->service);
    }

    /**
     * @test
     * @covers ::increment
     * @covers ::decrement
     * @covers ::load
     */
    public function decrement()
    {
        $this->memcachedAdaper->decrement($this->service);
        $this->assertSame(0, $this->memcachedAdaper->load($this->service));

        $this->memcachedAdaper->increment($this->service);
        $this->memcachedAdaper->increment($this->service);
        $this->memcachedAdaper->increment($this->service);
        $this->memcachedAdaper->decrement($this->service);
        $this->assertSame(2, $this->memcachedAdaper->load($this->service));
    }

    /**
     * @test
     * @covers ::decrement
     */
    public function decrementThrowsException()
    {
        $m = $this->getMockBuilder(\Memcached::class)
            ->setMethods(['decrement'])
            ->getMock();
        $m->expects($this->once())
            ->method('decrement')
            ->willReturn(false);

        $adapter = new Memcached($m);

        $this->expectException(StorageException::class);
        $adapter->decrement($this->service);
    }

    /**
     * @test
     * @covers ::saveLastFailureTime
     * @covers ::loadLastFailureTime
     */
    public function saveAndLoadLastFailureTime()
    {
        $time = time();
        $this->memcachedAdaper->saveLastFailureTime($this->service, $time);
        $this->assertSame($time, $this->memcachedAdaper->loadLastFailureTime($this->service));
    }

    /**
     * @test
     * @covers ::saveLastFailureTime
     */
    public function saveLastFailureTimeThrowsException()
    {
        $m = $this->getMockBuilder(\Memcached::class)
            ->setMethods(['set'])
            ->getMock();
        $m->expects($this->once())
            ->method('set')
            ->willReturn(false);

        $adapter = new Memcached($m);

        $this->expectException(StorageException::class);
        $adapter->saveLastFailureTime($this->service, time());
    }

    /**
     * @test
     * @covers ::loadLastFailureTime
     */
    public function loadLastFailureTimeThrowsException()
    {
        $m = $this->getMockBuilder(\Memcached::class)
            ->setMethods(['getResultCode'])
            ->getMock();
        $m->expects($this->once())
            ->method('getResultCode')
            ->willReturn(\Memcached::RES_FAILURE);

        $adapter = new Memcached($m);

        $this->expectException(StorageException::class);
        $adapter->loadLastFailureTime($this->service);
    }

    /**
     * @test
     * @covers ::saveStatus
     * @covers ::loadStatus
     */
    public function saveAndLoadStatus()
    {
        $status = Ganesha::STATUS_TRIPPED;
        $this->memcachedAdaper->saveStatus($this->service, $status);
        $this->assertSame($status, $this->memcachedAdaper->loadStatus($this->service));
    }

    /**
     * @test
     * @covers ::saveStatus
     */
    public function saveStatusThrowsException()
    {
        $m = $this->getMockBuilder(\Memcached::class)
            ->setMethods(['set'])
            ->getMock();
        $m->expects($this->once())
            ->method('set')
            ->willReturn(false);

        $adapter = new Memcached($m);

        $this->expectException(StorageException::class);
        $adapter->saveStatus($this->service, Ganesha::STATUS_TRIPPED);
    }

    /**
     * @test
     * @covers ::loadStatus
     */
    public function loadStatusThrowsException()
    {
        $m = $this->getMockBuilder(\Memcached::class)
            ->setMethods(['getResultCode'])
            ->getMock();
        $m->expects($this->once())
            ->method('getResultCode')
            ->willReturn(\Memcached::RES_FAILURE);

        $adapter = new Memcached($m);

        $this->expectException(StorageException::class);
        $adapter->loadStatus($this->service);
    }

    /**
     * @test
     * @requires PHP 7.0
     * @covers ::reset
     */
    public function resetWillDoNothingIfNoDataExists()
    {
        $m = $this->getMockBuilder(\Memcached::class)
            ->setMethods(['getStats', 'getAllKeys', 'getResultCode'])
            ->getMock();
        $m->expects($this->once())
            ->method('getStats')
            ->willReturn(['localhost:11211' => ['pid' => 1]]);

        $m->expects($this->once())
            ->method('getAllKeys')
            ->willReturn(false);

        $m->expects($this->once())
            ->method('getResultCode')
            ->willReturn(\Memcached::RES_SUCCESS);

        $adapter = new Memcached($m);
        $adapter->reset();
    }

    /**
     * @test
     * @requires PHP 7.0
     * @covers ::reset
     */
    public function resetThrowsExceptionWhenFailedToGetStats()
    {
        $m = $this->getMockBuilder(\Memcached::class)
            ->setMethods(['getStats'])
            ->getMock();
        $m->expects($this->once())
            ->method('getStats')
            ->willReturn(false);

        $adapter = new Memcached($m);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Couldn\'t connect to memcached.');
        $adapter->reset();
    }

    /**
     * @test
     * @requires PHP 7.0
     * @covers ::reset
     */
    public function resetThrowsExceptionWhenFailedToGetAllKeys()
    {
        $m = $this->getMockBuilder(\Memcached::class)
            ->setMethods(['getStats', 'getAllKeys', 'getResultCode'])
            ->getMock();
        $m->expects($this->once())
            ->method('getStats')
            ->willReturn(['localhost:11211' => ['pid' => 1]]);

        $m->expects($this->once())
            ->method('getAllKeys')
            ->willReturn(false);

        $m->expects($this->once())
            ->method('getResultCode')
            ->willReturn(\Memcached::RES_FAILURE);

        $adapter = new Memcached($m);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageRegExp('/^failed to get memcached keys/');
        $adapter->reset();
    }

    /**
     * @test
     * @dataProvider isGaneshaDataProvider
     * @covers ::isGaneshaData
     */
    public function isGaneshaData($key, $expected)
    {
        $this->assertSame($expected, $this->memcachedAdaper->isGaneshaData($key));
    }

    public function isGaneshaDataProvider()
    {
        return [
            ['ganesha_test_success', true],
            ['ganesha_test_failure', true],
            ['ganesha_test_rejection', true],
            ['ganesha_test_last_failure_time', true],
            ['ganesha_test_status', true],
            ['ganesha_ganesha_success', true],
            ['ganesha_success_success', true],
            ['ganesha_http://example.com_success', true],
            ['ganeshaa_test_success', false],
            ['ganesha_test_successs', false],
            ['ganesha_test_failuree', false],
            ['ganesha_test_rejectionn', false],
            ['ganesha_test_last_failure_timee', false],
            ['ganesha_test_statuss', false],
        ];
    }

    /**
     * @test
     */
    public function outdatedCountsShouldBeEvictedInCaseOfRateStrategy(): void
    {
        $timeWindow = 1;

        // Build an instance with `Rate` strategy
        $ganesha = Ganesha\Builder::withRateStrategy()
            ->adapter($this->memcachedAdaper)
            ->timeWindow($timeWindow)
            ->failureRateThreshold(50)
            ->minimumRequests(1)
            ->intervalToHalfOpen(10)
            ->build();

        // Record successes.
        // Since Memcached adapter implements `TumblingTimeWindow`, the count is recorded into a key which based on timestamp.
        $serviceName = 'outdatedCountsShouldBeEvicted';
        $ganesha->success($serviceName);
        $ganesha->success($serviceName);
        $ganesha->success($serviceName);
        $ganesha->failure($serviceName);
        $ganesha->failure($serviceName);

        $reflection = new \ReflectionMethod(Ganesha\Strategy\Rate::class, 'serviceNameDecorator');
        $reflection->setAccessible(true);
        $serviceNameDecorator = $reflection->invokeArgs(null, [$timeWindow]);
        $storageKeys = new Ganesha\Storage\StorageKeys();

        $successKeyForTheTumblingTimeWindow = $storageKeys->prefix() . $serviceNameDecorator($serviceName) . $storageKeys->success();
        $failureKeyForTheTumblingTimeWindow = $storageKeys->prefix() . $serviceNameDecorator($serviceName) . $storageKeys->failure();

        // The success count `3` could be obtained as the `TumblingTimeWindow` is still valid.
        self::assertSame(
            3,
            (int)$this->memcached->get($successKeyForTheTumblingTimeWindow)
        );
        // The failure count `2` could be obtained as the `TumblingTimeWindow` is still valid.
        self::assertSame(
            2,
            (int)$this->memcached->get($failureKeyForTheTumblingTimeWindow)
        );

        // Since sleeping 11 seconds as below, the `TumblingTimeWindow` contains the success count recorded above is outdated.
        sleep(11);

        // The count should be got cleared as the `TumblingTimeWindow` is outdated at this point.
        self::assertFalse($this->memcached->get($successKeyForTheTumblingTimeWindow));
        self::assertSame(
            \Memcached::RES_NOTFOUND,
            $this->memcached->getResultCode()
        );

        self::assertFalse($this->memcached->get($failureKeyForTheTumblingTimeWindow));
        self::assertSame(
            \Memcached::RES_NOTFOUND,
            $this->memcached->getResultCode()
        );
    }
}
