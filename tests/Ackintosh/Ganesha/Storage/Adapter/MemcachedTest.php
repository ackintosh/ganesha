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
        $this->memcachedAdaper->save($this->serviceName, 1);
        $this->assertSame(1, $this->memcachedAdaper->load($this->serviceName));
    }

    /**
     * @test
     */
    public function increment()
    {
        $this->memcachedAdaper->increment($this->serviceName);
        $this->assertSame(1, $this->memcachedAdaper->load($this->serviceName));
        $this->memcachedAdaper->increment($this->serviceName);
        $this->assertSame(2, $this->memcachedAdaper->load($this->serviceName));
    }

    /**
     * @test
     */
    public function decrement()
    {
        $this->memcachedAdaper->decrement($this->serviceName);
        $this->assertSame(0, $this->memcachedAdaper->load($this->serviceName));

        $this->memcachedAdaper->increment($this->serviceName);
        $this->memcachedAdaper->increment($this->serviceName);
        $this->memcachedAdaper->increment($this->serviceName);
        $this->memcachedAdaper->decrement($this->serviceName);
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
        $status = Ganesha::STATUS_TRIPPED;
        $this->memcachedAdaper->saveStatus($this->serviceName, $status);
        $this->assertSame($status, $this->memcachedAdaper->loadStatus($this->serviceName));
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
        return array(
            array('ganesha_test_success', true),
            array('ganesha_test_failure', true),
            array('ganesha_test_rejection', true),
            array('ganesha_test_last_failure_time', true),
            array('ganesha_test_status', true),
            array('ganesha_ganesha_success', true),
            array('ganesha_success_success', true),
            array('ganesha_http://example.com_success', true),
            array('ganeshaa_test_success', false),
            array('ganesha_test_successs', false),
            array('ganesha_test_failuree', false),
            array('ganesha_test_rejectionn', false),
            array('ganesha_test_last_failure_timee', false),
            array('ganesha_test_statuss', false),
        );
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function resetThrowsExceptionWhenFailedToConnectToMemcached()
    {
        $mock = $this->getMockBuilder('\Memcached')
            ->setMethods(array('getStats'))
            ->getMock();
        $mock->method('getStats')
            ->will($this->returnValue(false));

        $memcachedAdapter = new Memcached($mock);
        $memcachedAdapter->reset();
    }
}
