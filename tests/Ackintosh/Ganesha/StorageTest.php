<?php
namespace Ackintosh\Ganesha;

use Ackintosh\Ganesha;
use Ackintosh\Ganesha\Storage\Adapter\Hash;

class StorageTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function savesStatus()
    {
        $serviceName = 'test';
        $storage = new Storage(new Hash(), $ttl = 60, null);
        $this->assertSame($storage->getStatus($serviceName), Ganesha::STATUS_CALMED_DOWN);
        $storage->setStatus($serviceName, Ganesha::STATUS_TRIPPED);
        $this->assertSame($storage->getStatus($serviceName), Ganesha::STATUS_TRIPPED);
    }
}
