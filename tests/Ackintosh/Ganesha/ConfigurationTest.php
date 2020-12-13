<?php
namespace Ackintosh\Ganesha;

use Ackintosh\Ganesha\Storage\StorageKeys;
use PHPUnit\Framework\TestCase;

class ConfigurationTest extends TestCase
{
    /**
     * @test
     */
    public function setStorageKeysAsDefaults()
    {
        $this->assertInstanceOf(StorageKeys::class, (new Configuration([]))->storageKeys());
    }

    /**
     * @test
     */
    public function dontOverrideTheSpecifiedParameter()
    {
        $c = new Configuration([
            Configuration::STORAGE_KEYS => new TestStorageKey()
        ]);
        $this->assertInstanceOf(TestStorageKey::class, $c->storageKeys());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage DateTime should be an instance of AdapterInterface
     */
    public function validateAdapter()
    {
        Configuration::validate([Configuration::ADAPTER => new \DateTime]);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage DateTime should be an instance of StorageKeysInterface
     */
    public function validateStorageKey()
    {
        Configuration::validate([Configuration::STORAGE_KEYS => new \DateTime()]);
    }

    /**
     * @test
     * @dataProvider validateIntegerProvider
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessageRegExp /^[a-zA-Z]+ should be an positive integer$/
     */
    public function validateInteger(string $key)
    {
        Configuration::validate([$key => 0]);
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
        Configuration::validate([Configuration::FAILURE_RATE_THRESHOLD => 101]);
    }
}

class TestStorageKey extends StorageKeys
{
}
