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
    private $service = 'testService';

    public function setUp()
    {
        parent::setUp();
        $m = new \Memcached();
        $m->addServer(
            getenv('GANESHA_EXAMPLE_MEMCACHED') ? getenv('GANESHA_EXAMPLE_MEMCACHED') : 'localhost',
            11211
        );
        $m->delete($this->service);
        $this->memcachedAdaper = new Memcached($m);
    }

    /**
     * @test
     */
    public function supportsCountStrategy()
    {
        $this->assertTrue($this->memcachedAdaper->supportCountStrategy());
    }

    /**
     * @test
     */
    public function supportsRateStrategy()
    {
        $this->assertTrue($this->memcachedAdaper->supportRateStrategy());
    }

    /**
     * @test
     */
    public function saveAndLoad()
    {
        $this->memcachedAdaper->save($this->service, 1);
        $this->assertSame(1, $this->memcachedAdaper->load($this->service));
    }

    /**
     * @test
     * @expectedException \Ackintosh\Ganesha\Exception\StorageException
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
        $adapter->load($this->service);
    }

    /**
     * @test
     * @expectedException \Ackintosh\Ganesha\Exception\StorageException
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
        $adapter->save($this->service, 'test');
    }

    /**
     * @test
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
     * @expectedException \Ackintosh\Ganesha\Exception\StorageException
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
        $adapter->increment($this->service);
    }

    /**
     * @test
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
     * @expectedException \Ackintosh\Ganesha\Exception\StorageException
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
        $adapter->decrement($this->service);
    }

    /**
     * @test
     */
    public function saveAndLoadLastFailureTime()
    {
        $time = time();
        $this->memcachedAdaper->saveLastFailureTime($this->service, $time);
        $this->assertSame($time, $this->memcachedAdaper->loadLastFailureTime($this->service));
    }

    /**
     * @test
     * @expectedException \Ackintosh\Ganesha\Exception\StorageException
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
        $adapter->saveLastFailureTime($this->service, time());
    }

    /**
     * @test
     * @expectedException \Ackintosh\Ganesha\Exception\StorageException
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
        $adapter->loadLastFailureTime($this->service);
    }

    /**
     * @test
     */
    public function saveAndLoadStatus()
    {
        $status = Ganesha::STATUS_TRIPPED;
        $this->memcachedAdaper->saveStatus($this->service, $status);
        $this->assertSame($status, $this->memcachedAdaper->loadStatus($this->service));
    }

    /**
     * @test
     * @expectedException \Ackintosh\Ganesha\Exception\StorageException
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
        $adapter->saveStatus($this->service, Ganesha::STATUS_TRIPPED);
    }

    /**
     * @test
     * @expectedException \Ackintosh\Ganesha\Exception\StorageException
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
        $adapter->loadStatus($this->service);
    }

    /**
     * @test
     * @requires PHP 7.0
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
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Couldn't connect to memcached.
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
        $adapter->reset();
    }

    /**
     * @test
     * @requires PHP 7.0
     * @expectedException \RuntimeException
     * @expectedExceptionMessageRegExp /\Afailed to get memcached keys/
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
        $adapter->reset();
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
}
