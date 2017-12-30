<?php
namespace Ackintosh\Ganesha\Storage\Adapter;

use Ackintosh\Ganesha;

class MemcachedTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Memcached
     */
    private $memcachedAdaper;

    /**
     * @var string
     */
    private $resource = 'testService';

    public function setUp()
    {
        parent::setUp();
        $m = new \Memcached();
        $m->addServer(
            getenv('GANESHA_EXAMPLE_MEMCACHED') ? getenv('GANESHA_EXAMPLE_MEMCACHED') : 'localhost',
            11211
        );
        $m->delete($this->resource);
        $this->memcachedAdaper = new Memcached($m);
    }

    /**
     * @test
     */
    public function saveAndLoad()
    {
        $this->memcachedAdaper->save($this->resource, 1);
        $this->assertSame(1, $this->memcachedAdaper->load($this->resource));
    }

    /**
     * @test
     */
    public function increment()
    {
        $this->memcachedAdaper->increment($this->resource);
        $this->assertSame(1, $this->memcachedAdaper->load($this->resource));
        $this->memcachedAdaper->increment($this->resource);
        $this->assertSame(2, $this->memcachedAdaper->load($this->resource));
    }

    /**
     * @test
     */
    public function decrement()
    {
        $this->memcachedAdaper->decrement($this->resource);
        $this->assertSame(0, $this->memcachedAdaper->load($this->resource));

        $this->memcachedAdaper->increment($this->resource);
        $this->memcachedAdaper->increment($this->resource);
        $this->memcachedAdaper->increment($this->resource);
        $this->memcachedAdaper->decrement($this->resource);
        $this->assertSame(2, $this->memcachedAdaper->load($this->resource));
    }

    /**
     * @test
     */
    public function saveAndLoadLastFailureTime()
    {
        $time = time();
        $this->memcachedAdaper->saveLastFailureTime($this->resource, $time);
        $this->assertSame($time, $this->memcachedAdaper->loadLastFailureTime($this->resource));
    }

    /**
     * @test
     */
    public function saveAndLoadStatus()
    {
        $status = Ganesha::STATUS_TRIPPED;
        $this->memcachedAdaper->saveStatus($this->resource, $status);
        $this->assertSame($status, $this->memcachedAdaper->loadStatus($this->resource));
    }

    /**
     * @test
     * @dataProvider isGaneshaDataProvider
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
     * @expectedException \RuntimeException
     */
    public function resetThrowsExceptionWhenFailedToConnectToMemcached()
    {
        $mock = $this->getMockBuilder('\Memcached')
            ->setMethods(['getStats'])
            ->getMock();
        $mock->method('getStats')
            ->will($this->returnValue(false));

        $memcachedAdapter = new Memcached($mock);
        $memcachedAdapter->reset();
    }
}
