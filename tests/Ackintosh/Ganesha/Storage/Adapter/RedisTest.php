<?php
namespace Ackintosh\Ganesha\Storage\Adapter;

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

    public function setUp()
    {
        parent::setUp();
        $r = new \Redis();
        $r->connect(
            getenv('GANESHA_EXAMPLE_REDIS') ? getenv('GANESHA_EXAMPLE_REDIS') : 'localhost'
        );
        $r->flushAll();
        $this->redisAdapter = new Redis($r);
    }

    /**
     * @test
     */
    public function saveAndLoad()
    {
        $this->redisAdapter->increment($this->resource);
        $this->assertSame(1, $this->redisAdapter->load($this->resource));
    }

    /**
     * @test
     */
    public function increment()
    {
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
    }
}
