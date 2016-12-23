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
        $storage = new Storage(new Hash(), $ttl = 60);
        $this->assertSame($storage->getStatus($serviceName), Ganesha::STATUS_CLOSE);
        $storage->setStatus($serviceName, Ganesha::STATUS_OPEN);
        $this->assertSame($storage->getStatus($serviceName), Ganesha::STATUS_OPEN);
    }
}
