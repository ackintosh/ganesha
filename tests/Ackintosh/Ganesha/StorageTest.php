<?php
namespace Ackintosh\Ganesha;

use Ackintosh\Ganesha;
use Ackintosh\Ganesha\Storage\Adapter\Memcached;

class StorageTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function savesStatus()
    {
        $m = new \Memcached();
        $m->addServer('localhost', 11211);
        $storage = new Storage(new Memcached($m), $ttl = 60, null);

        $serviceName = 'test';
        $this->assertSame($storage->getStatus($serviceName), Ganesha::STATUS_CALMED_DOWN);
        $storage->setStatus($serviceName, Ganesha::STATUS_TRIPPED);
        $this->assertSame($storage->getStatus($serviceName), Ganesha::STATUS_TRIPPED);
    }
}
