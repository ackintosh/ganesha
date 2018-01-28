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
        $m->addServer(
            getenv('GANESHA_EXAMPLE_MEMCACHED') ? getenv('GANESHA_EXAMPLE_MEMCACHED') : 'localhost',
            11211
        );
        $storage = new Storage(new Memcached($m), null);

        $service = 'test';
        $this->assertSame($storage->getStatus($service), Ganesha::STATUS_CALMED_DOWN);
        $storage->setStatus($service, Ganesha::STATUS_TRIPPED);
        $this->assertSame($storage->getStatus($service), Ganesha::STATUS_TRIPPED);
    }
}
