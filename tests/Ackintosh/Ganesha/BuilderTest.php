<?php
namespace Ackintosh\Ganesha;

use PHPUnit\Framework\TestCase;

class BuilderTest extends TestCase
{
    /**
     * @test
     */
    public function withRateStrategy()
    {
        $this->assertInstanceOf(
            'Ackintosh\Ganesha\Strategy\Rate\Builder',
            Builder::withRateStrategy()
        );
    }

    /**
     * @test
     */
    public function withCountStrategy()
    {
        $this->assertInstanceOf(
            'Ackintosh\Ganesha\Strategy\Count\Builder',
            Builder::withCountStrategy()
        );
    }
}