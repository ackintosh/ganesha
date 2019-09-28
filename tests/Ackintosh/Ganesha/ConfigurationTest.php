<?php
namespace Ackintosh\Ganesha;

use Ackintosh\Ganesha\Storage\StorageKeys;

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

    /**
     * @test
     */
    public function setStorageKeysAsDefaults()
    {
        $this->assertInstanceOf(StorageKeys::class, $this->configuration['storageKeys']);
    }

    /**
     * @test
     */
    public function dontOverrideTheSpecifiedParameter()
    {
        $c = new Configuration([
            'storageKeys' => new TestStorageKey()
        ]);
        $this->assertInstanceOf(TestStorageKey::class, $c['storageKeys']);
    }
}

class TestStorageKey extends StorageKeys
{
}
