<?php
namespace Ackintosh\Ganesha;

use Ackintosh\Ganesha\Storage\StorageKeys;
use PHPUnit\Framework\TestCase;

class ConfigurationTest extends TestCase
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
            Configuration::STORAGE_KEYS => new TestStorageKey()
        ]);
        $this->assertInstanceOf(TestStorageKey::class, $c['storageKeys']);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage DateTime should be an instance of AdapterInterface
     */
    public function validateAdapter()
    {
        (new Configuration([Configuration::ADAPTER => new \DateTime]))->validate();
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage DateTime should be an instance of StorageKeysInterface
     */
    public function validateStorageKey()
    {
        (new Configuration([Configuration::STORAGE_KEYS => new \DateTime()]))->validate();
    }

    /**
     * @test
     * @dataProvider validateIntegerProvider
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessageRegExp /^[a-zA-Z]+ should be an positive integer$/
     */
    public function validateInteger(string $key)
    {
        (new Configuration([$key => 0]))->validate();
    }

    public function validateIntegerProvider()
    {
        return [
            [Configuration::TIME_WINDOW],
            [Configuration::FAILURE_RATE_THRESHOLD],
            [Configuration::FAILURE_COUNT_THRESHOLD],
            [Configuration::MINIMUM_REQUESTS],
            [Configuration::INTERVAL_TO_HALF_OPEN],
        ];
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage failureRateThreshold should be equal or less than 100
     */
    public function validateFailureRateThreshold()
    {
        (new Configuration([Configuration::FAILURE_RATE_THRESHOLD => 101]))->validate();
    }
}

class TestStorageKey extends StorageKeys
{
}
