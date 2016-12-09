<?php
namespace Ackintosh;

class GaneshaTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function ganesha()
    {
        $this->assertInstanceOf('\Ackintosh\Ganesha', new Ganesha());
    }
}
