<?php
namespace Ackintosh\Ganesha\Storage\Adapter;

use Ackintosh\Ganesha;
use Ackintosh\Ganesha\Configuration;

abstract class AbstractRedisTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Redis
     */
    private $redisAdapter;

    /**
     * @var string
     */
    private $service = 'testService';

    /**
     * @var int
     */
    const TIME_WINDOW = 3;

    /**
     * @return \Redis|\RedisArray|\RedisCluster|\Predis\Client
     */
    abstract protected function getRedisConnection();

    public function setUp()
    {
        parent::setUp();

        $this->redisAdapter = new Redis($this->getRedisConnection());
        $configuration = new Configuration(['timeWindow' => self::TIME_WINDOW]);
        $this->redisAdapter->setConfiguration($configuration);
    }

    /**
     * @test
     */
    public function incrementAndLoad()
    {
        $this->redisAdapter->increment($this->service);
        $this->redisAdapter->increment($this->service);

        sleep(self::TIME_WINDOW);

        $this->redisAdapter->increment($this->service);
        $this->redisAdapter->increment($this->service);

        // Expired value will be remove
        $this->assertSame(2, $this->redisAdapter->load($this->service));
    }

    /**
     * @test
     * @expectedException \Ackintosh\Ganesha\Exception\StorageException
     * @expectedExceptionMessageRegExp /\AFailed to add sorted set/
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
     * @expectedExceptionMessageRegExp /\AFailed to load cardinality/
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
        $this->redisAdapter->increment($this->service);

        sleep(3);

        $this->redisAdapter->increment($this->service);
        $lastFailureTime = microtime(true);

        $this->assertEquals(
            (int)$lastFailureTime,
            $this->redisAdapter->loadLastFailureTime($this->service),
            null,
            1
        );
    }

    /**
     * @test
     */
    public function loadLastFailureTimeReturnsNullIfNoData()
    {
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
     * @expectedExceptionMessageRegExp /\AFailed to load status/
     */
    public function loadStatusThrowsExceptionWhenFailedToRunset()
    {
        $mock = $this->getMockBuilder(\Redis::class)->getMock();
        $mock->method('get')
            ->willReturn(false);

        $this->assertNull((new Redis($mock))->loadStatus($this->service));
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
