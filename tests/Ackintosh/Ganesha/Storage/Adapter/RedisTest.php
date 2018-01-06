<?php
namespace Ackintosh\Ganesha\Storage\Adapter;

use Ackintosh\Ganesha;
use Ackintosh\Ganesha\Configuration;

class RedisTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Redis
     */
    private $redisAdapter;

    /**
     * @var string
     */
    private $resource = 'testService';

    /**
     * @var int
     */
    const TIME_WINDOW = 3;

    public function setUp()
    {
        parent::setUp();
        $r = new \Redis();
        $r->connect(
            getenv('GANESHA_EXAMPLE_REDIS') ? getenv('GANESHA_EXAMPLE_REDIS') : 'localhost'
        );
        $r->flushAll();
        $this->redisAdapter = new Redis($r);
        $configuration = new Configuration(['timeWindow' => self::TIME_WINDOW]);
        $this->redisAdapter->setConfiguration($configuration);
    }

    /**
     * @test
     */
    public function incrementAndLoad()
    {
        $this->redisAdapter->increment($this->resource);
        $this->redisAdapter->increment($this->resource);

        sleep(self::TIME_WINDOW);

        $this->redisAdapter->increment($this->resource);
        $this->redisAdapter->increment($this->resource);

        // Expired value will be remove
        $this->assertSame(2, $this->redisAdapter->load($this->resource));
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

        (new Redis($mock))->increment($this->resource);
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

        (new Redis($mock))->increment($this->resource);
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

        (new Redis($mock))->load($this->resource);
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

        (new Redis($mock))->load($this->resource);
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

        (new Redis($mock))->load($this->resource);
    }

    /**
     * @test
     */
    public function loadLastFailureTime()
    {
        $this->redisAdapter->increment($this->resource);

        sleep(3);

        $this->redisAdapter->increment($this->resource);
        $lastFailureTime = microtime(true);

        $this->assertEquals(
            (int)$lastFailureTime,
            $this->redisAdapter->loadLastFailureTime($this->resource),
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

        $this->assertNull((new Redis($mock))->loadLastFailureTime($this->resource));
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

        (new Redis($mock))->loadLastFailureTime($this->resource);
    }

    /**
     * @test
     */
    public function saveAndLoadStatus()
    {
        $this->redisAdapter->saveStatus($this->resource, Ganesha::STATUS_TRIPPED);
        $this->assertSame(Ganesha::STATUS_TRIPPED, $this->redisAdapter->loadStatus($this->resource));
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

        $this->assertNull((new Redis($mock))->saveStatus($this->resource, Ganesha::STATUS_TRIPPED));
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

        (new Redis($mock))->saveStatus($this->resource, Ganesha::STATUS_TRIPPED);
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

        $this->assertNull((new Redis($mock))->loadStatus($this->resource));
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

        (new Redis($mock))->loadStatus($this->resource);
    }
}
