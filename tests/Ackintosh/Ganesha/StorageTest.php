<?php
namespace Ackintosh\Ganesha;

use Ackintosh\Ganesha;

class StorageTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function savesStatus()
    {
        $storage = new Storage();
        $this->assertSame($storage->getStatus(), Ganesha::STATUS_CLOSE);
        $storage->setStatus(Ganesha::STATUS_OPEN);
        $this->assertSame($storage->getStatus(), Ganesha::STATUS_OPEN);
    }
}
