<?php

namespace Ackintosh\Ganesha\Storage\Adapter;

use Ackintosh\Ganesha;
use Ackintosh\Ganesha\Configuration;
use Ackintosh\Ganesha\Exception\StorageException;
use PHPUnit\Framework\TestCase;

abstract class AbstractRedisTest extends TestCase
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

    public function setUp()
    {
        parent::setUp();

        $this->redisAdapter = new Redis($this->getRedisConnection());
        $configuration = new Configuration(['timeWindow' => self::TIME_WINDOW]);
        $this->redisAdapter->setConfiguration($configuration);
    }

    /**
     * @return \Redis|\RedisArray|\RedisCluster|\Predis\Client
     */
    abstract protected function getRedisConnection();

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
     * @expectedException \Ackintosh\Ganesha\Exception\StorageException
     * @expectedExceptionMessageRegExp /\AFailed to execute zAdd command/
     */
    public function incrementThrowsExceptionWhenFailedToRunzAdd()
    {
        $mock = $this->getMockBuilder(\Redis::class)->getMock();
        $mock->method('zAdd')
            ->willReturn(false);

        (new Redis($mock))->increment($this->service);
    }

    /**
     * @test
     * @expectedException \Ackintosh\Ganesha\Exception\StorageException
     * @expectedExceptionMessage exception test
     */
    public function incrementThrowsException()
    {
        $mock = $this->getMockBuilder(\Redis::class)->getMock();
        $mock->method('zAdd')
            ->willThrowException(new \RedisException('exception test'));

        (new Redis($mock))->increment($this->service);
    }

    /**
     * @test
     * @expectedException \Ackintosh\Ganesha\Exception\StorageException
     * @expectedExceptionMessageRegExp /\AFailed to remove expired elements/
     */
    public function loadThrowsExceptionWhenFailedToRunzRemRangeByScore()
    {
        $mock = $this->getMockBuilder(\Redis::class)->getMock();
        $mock->method('zRemRangeByScore')
            ->willReturn(false);

        (new Redis($mock))->load($this->service);
    }

    /**
     * @test
     * @expectedException \Ackintosh\Ganesha\Exception\StorageException
     * @expectedExceptionMessageRegExp /\AFailed to execute zCard command/
     */
    public function loadThrowsExceptionWhenFailedToRunzCard()
    {
        $mock = $this->getMockBuilder(\Redis::class)->getMock();
        $mock->method('zCard')
            ->willReturn(false);

        (new Redis($mock))->load($this->service);
    }

    /**
     * @test
     * @expectedException \Ackintosh\Ganesha\Exception\StorageException
     * @expectedExceptionMessage exception test
     */
    public function loadThrowsException()
    {
        $mock = $this->getMockBuilder(\Redis::class)->getMock();
        $mock->method('zRemRangeByScore')
            ->willThrowException(new \RedisException('exception test'));

        (new Redis($mock))->load($this->service);
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

            $this->assertEquals(
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

        $this->assertNull((new Redis($mock))->loadLastFailureTime($this->service));
    }

    /**
     * @test
     * @expectedException \Ackintosh\Ganesha\Exception\StorageException
     * @expectedExceptionMessage exception test
     */
    public function loadLastFailureTimeThrowsException()
    {
        $mock = $this->getMockBuilder(\Redis::class)->getMock();
        $mock->method('zRange')
            ->willThrowException(new \RedisException('exception test'));

        (new Redis($mock))->loadLastFailureTime($this->service);
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
     * @expectedException \Ackintosh\Ganesha\Exception\StorageException
     * @expectedExceptionMessageRegExp /\AFailed to save status/
     */
    public function saveStatusThrowsExceptionWhenFailedToRunset()
    {
        $mock = $this->getMockBuilder(\Redis::class)->getMock();
        $mock->method('set')
            ->willReturn(false);

        $this->assertNull((new Redis($mock))->saveStatus($this->service, Ganesha::STATUS_TRIPPED));
    }

    /**
     * @test
     * @expectedException \Ackintosh\Ganesha\Exception\StorageException
     * @expectedExceptionMessage exception test
     */
    public function saveStatusThrowsException()
    {
        $mock = $this->getMockBuilder(\Redis::class)->getMock();
        $mock->method('set')
            ->willThrowException(new \RedisException('exception test'));

        (new Redis($mock))->saveStatus($this->service, Ganesha::STATUS_TRIPPED);
    }

    /**
     * @test
     * @expectedException \Ackintosh\Ganesha\Exception\StorageException
     * @expectedExceptionMessage exception test
     */
    public function loadStatusThrowsException()
    {
        $mock = $this->getMockBuilder(\Redis::class)->getMock();
        $mock->method('get')
            ->willThrowException(new \RedisException('exception test'));

        (new Redis($mock))->loadStatus($this->service);
    }
}
