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
     */
    public function validateAdapter()
    {
        $this->expectExceptionMessage("DateTime should be an instance of AdapterInterface");
        $this->expectException(\InvalidArgumentException::class);
        Configuration::validate([Configuration::ADAPTER => new \DateTime]);
    }

    /**
     * @test
     */
    public function validateStorageKey()
    {
        $this->expectExceptionMessage("DateTime should be an instance of StorageKeysInterface");
        $this->expectException(\InvalidArgumentException::class);
        Configuration::validate([Configuration::STORAGE_KEYS => new \DateTime()]);
    }

    /**
     * @test
     * @dataProvider validateIntegerProvider
     */
    public function validateInteger(string $key)
    {
        $this->expectExceptionMessageMatches("/^[a-zA-Z]+ should be an positive integer$/");
        $this->expectException(\InvalidArgumentException::class);
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
     */
    public function validateFailureRateThreshold()
    {
        $this->expectExceptionMessage("failureRateThreshold should be equal or less than 100");
        $this->expectException(\InvalidArgumentException::class);
        Configuration::validate([Configuration::FAILURE_RATE_THRESHOLD => 101]);
    }
}

class TestStorageKey extends StorageKeys
{
}
