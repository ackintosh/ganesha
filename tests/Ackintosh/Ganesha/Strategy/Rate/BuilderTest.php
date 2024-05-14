<?php

namespace Ackintosh\Ganesha\Strategy\Rate;

use Ackintosh\Ganesha\Storage\StorageKeysInterface;
use PHPUnit\Framework\TestCase;

class BuilderTest extends TestCase
{
    /**
     * @test
     */
    public function storageKeys()
    {
        $storageKeys = new class implements StorageKeysInterface {
            public function prefix(): string
            {
                return "test";
            }
            public function success(): string
            {
                return "test";
            }
            public function failure(): string
            {
                return "test";
            }
            public function rejection(): string
            {
                return "test";
            }
            public function lastFailureTime(): string
            {
                return "test";
            }
            public function status(): string
            {
                return "test";
            }
        };
        $this->assertInstanceOf('Ackintosh\Ganesha\Strategy\Rate\Builder', (new Builder)->storageKeys($storageKeys));
    }
}
