<?php

namespace Ackintosh\Ganesha\Storage\Adapter;

use Ackintosh\Ganesha;
use Ackintosh\Ganesha\Configuration;
use Ackintosh\Ganesha\Exception\StorageException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

abstract class AbstractRedisSpec extends TestCase
{
    /**
     * @var int
     */
    const TIME_WINDOW = 3;
    /**
     * @var Redis
     */
    private $redisAdapter;
    /**
     * @var string
     */
    private $service = 'testService';

    /**
     * @var Ganesha\Context
     */
    private $context;

    protected function setUp(): void
    {
        parent::setUp();

        $this->redisAdapter = new Redis($this->getRedisConnection());
        $configuration = new Configuration(['timeWindow' => self::TIME_WINDOW]);
        $this->context = new Ganesha\Context(
            Ganesha\Strategy\Rate::class,
            $this->redisAdapter,
            $configuration
        );
        $this->redisAdapter->setContext($this->context);
    }

    /**
     * @return \Redis|\RedisArray|\RedisCluster|\Predis\Client
     */
    abstract protected function getRedisConnection();

    private function createAdapterWithMock(MockObject $mock): Redis
    {
        $adapter = new Redis($mock);
        $adapter->setContext($this->context);
        return $adapter;
    }

    /**
     * @test
     */
    public function doesntSupportCountStrategy()
    {
        $this->assertFalse($this->redisAdapter->supportCountStrategy());
    }

    /**
     * @test
     */
    public function supportsRateStrategy()
    {
        $this->assertTrue($this->redisAdapter->supportRateStrategy());
    }

    /**
     * @test
     */
    public function incrementAndLoad()
    {
        try {
            $this->redisAdapter->increment($this->service);
            $this->redisAdapter->increment($this->service);

            usleep(self::TIME_WINDOW * 1000000 + 100);

            $this->redisAdapter->increment($this->service);
            $this->redisAdapter->increment($this->service);

            // Expired values will be removed
            $result = $this->redisAdapter->load($this->service);
        } catch (StorageException $exception) {
            $this->fail($exception->getMessage());
        }

        $this->assertSame(2, $result);
    }

    /**
     * @test
     */
    public function incrementThrowsExceptionWhenFailedToRunzRemRangeByScore()
    {
        $this->expectExceptionMessageMatches("/\AFailed to remove expired elements/");
        $this->expectException(StorageException::class);
        $mock = $this->getMockBuilder(\Redis::class)->getMock();
        $mock->method('zRemRangeByScore')
            ->willReturn(false);

        $this->createAdapterWithMock($mock)->increment($this->service);
    }

    /**
     * @test
     */
    public function incrementThrowsExceptionWhenFailedToRunzAdd()
    {
        $this->expectExceptionMessageMatches("/\AFailed to execute zAdd command/");
        $this->expectException(StorageException::class);
        $mock = $this->getMockBuilder(\Redis::class)->getMock();
        $mock->method('zAdd')
            ->willReturn(false);

        $this->createAdapterWithMock($mock)->increment($this->service);
    }

    /**
     * @test
     */
    public function incrementThrowsException()
    {
        $this->expectExceptionMessage('exception test');
        $this->expectException(StorageException::class);
        $mock = $this->getMockBuilder(\Redis::class)->getMock();
        $mock->method('zAdd')
            ->willThrowException(new \RedisException('exception test'));

        $this->createAdapterWithMock($mock)->increment($this->service);
    }

    /**
     * @test
     */
    public function loadThrowsExceptionWhenFailedToRunzRemRangeByScore()
    {
        $this->expectExceptionMessageMatches("/\AFailed to remove expired elements/");
        $this->expectException(StorageException::class);
        $mock = $this->getMockBuilder(\Redis::class)->getMock();
        $mock->method('zRemRangeByScore')
            ->willReturn(false);

        $this->createAdapterWithMock($mock)->load($this->service);
    }

    /**
     * @test
     */
    public function loadThrowsExceptionWhenFailedToRunzCard()
    {
        $this->expectExceptionMessageMatches("/\AFailed to execute zCard command/");
        $this->expectException(StorageException::class);
        $mock = $this->getMockBuilder(\Redis::class)->getMock();
        $mock->method('zCard')
            ->willReturn(false);

        $this->createAdapterWithMock($mock)->load($this->service);
    }

    /**
     * @test
     */
    public function loadThrowsException()
    {
        $this->expectExceptionMessage('exception test');
        $this->expectException(StorageException::class);
        $mock = $this->getMockBuilder(\Redis::class)->getMock();
        $mock->method('zCard')
            ->willThrowException(new \RedisException('exception test'));

        $this->createAdapterWithMock($mock)->load($this->service);
    }

    /**
     * @test
     */
    public function loadLastFailureTime()
    {
        try {
            $this->redisAdapter->increment($this->service);

            sleep(3);

            $this->redisAdapter->increment($this->service);
            $lastFailureTime = microtime(true);

            $this->assertSame(
                (int)$lastFailureTime,
                $this->redisAdapter->loadLastFailureTime($this->service),
                '',
                1
            );
        } catch (StorageException $exception) {
            $this->fail($exception->getMessage());
        }
    }

    /**
     * @test
     */
    public function loadLastFailureTimeReturnsNullIfNoData()
    {
        // TODO: replace the mock with a real object
        $mock = $this->getMockBuilder(\Redis::class)->getMock();
        $mock->method('zRange')
            ->willReturn(false);

        $this->assertNull($this->createAdapterWithMock($mock)->loadLastFailureTime($this->service));
    }

    /**
     * @test
     */
    public function loadLastFailureTimeThrowsException()
    {
        $this->expectExceptionMessage('exception test');
        $this->expectException(StorageException::class);
        $mock = $this->getMockBuilder(\Redis::class)->getMock();
        $mock->method('zRange')
            ->willThrowException(new \RedisException('exception test'));

        $this->createAdapterWithMock($mock)->loadLastFailureTime($this->service);
    }

    /**
     * @test
     */
    public function loadStatusReturns_STATUS_CALMED_DOWN_AsInitialStatus()
    {
        $this->assertSame(Ganesha::STATUS_CALMED_DOWN, $this->redisAdapter->loadStatus($this->service));
    }

    /**
     * @test
     */
    public function saveAndLoadStatus()
    {
        $this->redisAdapter->saveStatus($this->service, Ganesha::STATUS_TRIPPED);
        $this->assertSame(Ganesha::STATUS_TRIPPED, $this->redisAdapter->loadStatus($this->service));
    }

    /**
     * @test
     */
    public function saveStatusThrowsExceptionWhenFailedToRunset()
    {
        $this->expectExceptionMessageMatches("/\AFailed to save status/");
        $this->expectException(StorageException::class);
        $mock = $this->getMockBuilder(\Redis::class)->getMock();
        $mock->method('set')
            ->willReturn(false);

        $this->assertNull($this->createAdapterWithMock($mock)->saveStatus($this->service, Ganesha::STATUS_TRIPPED));
    }

    /**
     * @test
     *
     *
     */
    public function saveStatusThrowsException()
    {
        $this->expectExceptionMessage('exception test');
        $this->expectException(StorageException::class);
        $mock = $this->getMockBuilder(\Redis::class)->getMock();
        $mock->method('set')
            ->willThrowException(new \RedisException('exception test'));

        $this->createAdapterWithMock($mock)->saveStatus($this->service, Ganesha::STATUS_TRIPPED);
    }

    /**
     * @test
     */
    public function loadStatusThrowsException()
    {
        $this->expectExceptionMessage('exception test');
        $this->expectException(\Ackintosh\Ganesha\Exception\StorageException::class);
        $mock = $this->getMockBuilder(\Redis::class)->getMock();
        $mock->method('get')
            ->willThrowException(new \RedisException('exception test'));

        $this->createAdapterWithMock($mock)->loadStatus($this->service);
    }
}
