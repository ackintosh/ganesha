<?php
namespace Ackintosh\Ganesha;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Configuration
     */
    private $configuration;

    public function setUp()
    {
        parent::setUp();
        $this->configuration = new Configuration([
            'foo' => 'bar',
        ]);
    }

    /**
     * @test
     */
    public function offsetSet()
    {
        $this->configuration['testkey'] = 'testvalue';
        $this->assertSame('testvalue', $this->configuration['testkey']);
    }

    /**
     * @test
     */
    public function offsetExists()
    {
        $this->assertTrue(isset($this->configuration['foo']));
        $this->assertFalse(isset($this->configuration['xxx']));
    }

    /**
     * @test
     */
    public function offsetUnset()
    {
        unset($this->configuration['foo']);
        $this->assertNull($this->configuration['foo']);
    }

    /**
     * @test
     */
    public function offsetGet()
    {
        $this->assertSame('bar', $this->configuration['foo']);
        $this->assertNull($this->configuration['xxx']);
    }
}
