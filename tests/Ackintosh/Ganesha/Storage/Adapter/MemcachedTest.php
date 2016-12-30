<?php
namespace Ackintosh\Ganesha\Storage\Adapter;

use Ackintosh\Ganesha;

class MemcachedTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Memcached
     */
    private $memcachedAdaper;
    private $ttl = 60;

    /**
     * @var string
     */
    private $serviceName = 'testService';

    public function setUp()
    {
        parent::setUp();
        $m = new \Memcached();
        $m->addServer('localhost', 11211);
        $m->delete($this->serviceName);
        $this->memcachedAdaper = new Memcached($m);
    }

    /**
     * @test
     */
    public function saveAndLoad()
    {
        $this->memcachedAdaper->save($this->serviceName, 1, $this->ttl);
        $this->assertSame(1, $this->memcachedAdaper->load($this->serviceName));
    }

    /**
     * @test
     */
    public function increment()
    {
        $this->memcachedAdaper->increment($this->serviceName, $this->ttl);
        $this->assertSame(1, $this->memcachedAdaper->load($this->serviceName));
        $this->memcachedAdaper->increment($this->serviceName, $this->ttl);
        $this->assertSame(2, $this->memcachedAdaper->load($this->serviceName));
    }

    /**
     * @test
     */
    public function decrement()
    {
        $this->memcachedAdaper->decrement($this->serviceName, $this->ttl);
        $this->assertSame(0, $this->memcachedAdaper->load($this->serviceName));

        $this->memcachedAdaper->increment($this->serviceName, $this->ttl);
        $this->memcachedAdaper->increment($this->serviceName, $this->ttl);
        $this->memcachedAdaper->increment($this->serviceName, $this->ttl);
        $this->memcachedAdaper->decrement($this->serviceName, $this->ttl);
        $this->assertSame(2, $this->memcachedAdaper->load($this->serviceName));
    }

    /**
     * @test
     */
    public function saveAndLoadLastFailureTime()
    {
        $time = time();
        $this->memcachedAdaper->saveLastFailureTime($this->serviceName, $time);
        $this->assertSame($time, $this->memcachedAdaper->loadLastFailureTime($this->serviceName));
    }

    /**
     * @test
     */
    public function saveAndLoadStatus()
    {
        $status = Ganesha::STATUS_OPEN;
        $this->memcachedAdaper->saveStatus($this->serviceName, $status);
        $this->assertSame($status, $this->memcachedAdaper->loadStatus($this->serviceName));
    }
}
