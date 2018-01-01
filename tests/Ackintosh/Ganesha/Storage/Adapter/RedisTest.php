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
    public function decrement()
    {
    }

    /**
     * @test
     */
    public function saveAndLoadLastFailureTime()
    {
    }

    /**
     * @test
     */
    public function saveAndLoadStatus()
    {
        $this->redisAdapter->saveStatus($this->resource, Ganesha::STATUS_TRIPPED);
        $this->assertSame(Ganesha::STATUS_TRIPPED, $this->redisAdapter->loadStatus($this->resource));
    }
}
