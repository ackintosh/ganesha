<?php

namespace Ackintosh\Ganesha\Storage\Adapter;

use Ackintosh\Ganesha;
use Ackintosh\Ganesha\Configuration;
use Ackintosh\Ganesha\Exception\StorageException;
use Ackintosh\Ganesha\Storage\StorageKeysInterface;
use APCuIterator;
use DateTime;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Ackintosh\Ganesha\Storage\Adapter\Apcu
 */
class ApcuTest extends TestCase
{
    private const EXPECT_KEY_REGEX = '/^\\^ .+( s| f| r| \\$| \\/)$/';

    protected function tearDown(): void
    {
        apcu_clear_cache();
        parent::tearDown();
    }

    /**
     * @test
     * @covers ::__construct
     */
    public function test_construct()
    {
        // Assert only that the $apcStore value passed to the constructor is
        // respected. We test the remainer of store() in test_save_success().
        $apcStore = $this->createMock(ApcuStore::class);
        $apcStore->expects($this->once())->method('store')
            ->willReturn(true);

        $apc = $this->getApcu($apcStore);
        $apc->save('abc', 123);
    }

    /**
     * @test
     * @covers ::supportCountStrategy
     */
    public function test_supportCountStrategy()
    {
        $this->assertTrue(
            $this->getApcu()->supportCountStrategy()
        );
    }

    /**
     * @test
     * @covers ::supportRateStrategy
     */
    public function test_supportRateStrategy()
    {
        $this->assertTrue(
            $this->getApcu()->supportRateStrategy()
        );
    }

    /**
     * @test
     * @covers ::setContext
     */
    public function test_setContext()
    {
        $configuration = $this->createMock(Configuration::class);
        $configuration->expects($this->once())->method('storageKeys')
            ->willReturn($this->getStorageKeys());
        $apc = $this->getApcu();

        $context = new Ganesha\Context(Ganesha\Strategy\Count::class, $apc, $configuration);

        $apc->setContext($context);
    }

    public function provide_load()
    {
        return [
            'found' => ['abc', 123, 123],
            'zero' => ['abc', 0, 0],
            'not found' => ['xyz', false, 0],
        ];
    }

    /**
     * @test
     * @dataProvider provide_load
     * @covers ::load
     */
    public function test_load(string $key, $returnValue, int $expectResult)
    {
        $apcStore = $this->createMock(ApcuStore::class);
        $apcStore->expects($this->once())->method('fetch')
            ->with($key)->willReturn($returnValue);
        $apc = $this->getApcu($apcStore);

        $this->assertSame(
            $expectResult,
            $apc->load($key)
        );
    }

    /**
     * @test
     * @covers ::load
     * @covers ::save
     * @covers ::reset
     */
    public function test_load_save_reset()
    {
        $key = $this->getSuccessKey();
        $apc = $this->getApcu();

        $this->assertSame(0, $apc->load($key));
        $apc->save($key, 123);
        $this->assertSame(123, $apc->load($key));
        $apc->reset();
        $this->assertSame(0, $apc->load($key));
    }

    /**
     * @test
     * @covers ::save
     */
    public function test_save_success()
    {
        $key = $this->getSuccessKey();
        $value = __LINE__;

        $apcStore = $this->createMock(ApcuStore::class);
        $apcStore->expects($this->once())->method('store')
            ->with($key, $value)->willReturn(true);
        $apc = $this->getApcu($apcStore);

        $apc->save($key, $value);
    }

    /**
     * @test
     * @covers ::save
     */
    public function test_save_failure()
    {
        $key = $this->getSuccessKey();
        $value = __LINE__;

        $apcStore = $this->createMock(ApcuStore::class);
        $apcStore->method('store')->willReturn(false);
        $apc = $this->getApcu($apcStore);

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Failed to set the value.');
        $apc->save($key, $value);
    }

    /**
     * @test
     * @covers ::increment
     * @covers ::decrement
     * @covers ::reset
     */
    public function test_increment_decrement_reset()
    {
        $key = $this->getSuccessKey();
        $apc = $this->getApcu();

        $this->assertSame(0, $apc->load($key));
        $apc->increment($key);
        $this->assertSame(1, $apc->load($key));
        $apc->decrement($key);
        $this->assertSame(0, $apc->load($key));
        $apc->decrement($key);
        $this->assertSame(0, $apc->load($key));
        $apc->increment($key);
        $this->assertSame(1, $apc->load($key));
        $apc->increment($key);
        $this->assertSame(2, $apc->load($key));
        $apc->reset();
        $this->assertSame(0, $apc->load($key));
    }

    /**
     * @test
     * @covers ::increment
     */
    public function test_increment_success()
    {
        $key = $this->getSuccessKey();

        $apcStore = $this->createMock(ApcuStore::class);
        $apcStore->expects($this->once())->method('inc')
            ->with($key, 1, $this->anything())
            ->will($this->returnCallback(
                function ($key, $step, &$success) {
                    $success = true;
                    return 1;
                }
            ));

        $apc = $this->getApcu($apcStore);

        $apc->increment($key);
    }

    /**
     * @test
     * @covers ::increment
     */
    public function test_increment_failure()
    {
        $key = $this->getSuccessKey();

        $apcStore = $this->createMock(ApcuStore::class);
        $apcStore->expects($this->once())->method('inc')
            ->will($this->returnCallback(
                function ($key, $step, &$success) {
                    $success = false;
                    return 0;
                }
            ));

        $apc = $this->getApcu($apcStore);

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Failed to increment failure count.');
        $apc->increment($key);
    }

    /**
     * @test
     * @covers ::decrement
     */
    public function test_decrement_success()
    {
        $key = $this->getSuccessKey();

        $apcStore = $this->createMock(ApcuStore::class);
        $apcStore->expects($this->once())->method('fetch')
            ->with($key)->willReturn(1);
        $apcStore->expects($this->once())->method('dec')
            ->with($key, 1, $this->anything())
            ->will($this->returnCallback(
                function ($key, $step, &$success) {
                    $success = true;
                    return 0;
                }
            ));
        $apcStore->expects($this->never())->method('inc');

        $apc = $this->getApcu($apcStore);

        $apc->decrement($key);
    }

    /**
     * @test
     * @covers ::decrement
     */
    public function test_decrement_alreadyZero()
    {
        $key = $this->getSuccessKey();

        $apcStore = $this->createMock(ApcuStore::class);
        $apcStore->expects($this->once())->method('fetch')
            ->with($key)->willReturn(0);
        $apcStore->expects($this->never())->method('dec');
        $apcStore->expects($this->never())->method('inc');

        $apc = $this->getApcu($apcStore);

        $apc->decrement($key);
    }

    /**
     * @test
     * @covers ::decrement
     */
    public function test_decrement_failure()
    {
        $key = $this->getSuccessKey();

        $apcStore = $this->createMock(ApcuStore::class);
        $apcStore->expects($this->once())->method('fetch')
            ->with($key)->willReturn(1);
        $apcStore->expects($this->once())->method('dec')
            ->will($this->returnCallback(
                function ($key, $step, &$success) {
                    $success = false;
                    return false;
                }
            ));
        $apcStore->expects($this->never())->method('inc');

        $apc = $this->getApcu($apcStore);

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Failed to decrement failure count.');
        $apc->decrement($key);
    }

    /**
     * Simulates a race condition where the value changed between load() and
     * dec().
     *
     * @test
     * @covers ::decrement
     */
    public function test_decrement_raceCondition()
    {
        $key = $this->getSuccessKey();

        $apcStore = $this->createMock(ApcuStore::class);
        $apcStore->expects($this->once())->method('fetch')
            ->with($key)->willReturn(1);
        $apcStore->expects($this->once())->method('dec')
            ->will($this->returnCallback(
                function ($key, $step, &$success) {
                    $success = true;
                    return -1;
                }
            ));
        $apcStore->expects($this->once())->method('inc')
            ->with($key, 1)
            ->will($this->returnCallback(
                function ($key, $step, &$success) {
                    $success = true;
                    return 0;
                }
            ));

        $apc = $this->getApcu($apcStore);

        $apc->decrement($key);
    }

    /**
     * @test
     * @covers ::decrement
     */
    public function test_decrement_raceConditionFailure()
    {
        $key = $this->getSuccessKey();

        $apcStore = $this->createMock(ApcuStore::class);
        $apcStore->expects($this->once())->method('fetch')
            ->with($key)->willReturn(1);
        $apcStore->expects($this->once())->method('dec')
            ->will($this->returnCallback(
                function ($key, $step, &$success) {
                    $success = true;
                    return -1;
                }
            ));
        $apcStore->expects($this->once())->method('inc')
            ->will($this->returnCallback(
                function ($key, $step, &$success) {
                    $success = false;
                    return false;
                }
            ));

        $apc = $this->getApcu($apcStore);

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Failed to decrement failure count.');
        $apc->decrement($key);
    }

    /**
     * @test
     * @covers ::saveLastFailureTime
     * @covers ::loadLastFailureTime
     * @covers ::reset
     */
    public function test_saveLastFailureTime_loadLastFailureTime_reset()
    {
        $key = $this->getLastFailureTimeKey();
        $time = (new DateTime('1985-10-21 16:29:00'))->getTimestamp();
        $outtaTime = (new DateTime('2015-10-21 16:29:00'))->getTimestamp();
        $apc = $this->getApcu();

        $this->assertNull($apc->loadLastFailureTime($key));
        $apc->saveLastFailureTime($key, $time);
        $this->assertSame($time, $apc->loadLastFailureTime($key));
        $apc->saveLastFailureTime($key, $outtaTime);
        $this->assertSame($outtaTime, $apc->loadLastFailureTime($key));
        $apc->reset();
        $this->assertNull($apc->loadLastFailureTime($key));
    }

    /**
     * @test
     * @covers ::saveLastFailureTime
     */
    public function test_saveLastFailureTime_success()
    {
        $key = $this->getLastFailureTimeKey();
        $time = (new DateTime('1985-10-26 01:21:00'))->getTimestamp();

        $apcStore = $this->createMock(ApcuStore::class);
        $apcStore->expects($this->once())->method('store')
            ->with($key, $time)
            ->willReturn(true);

        $apc = $this->getApcu($apcStore);

        $apc->saveLastFailureTime($key, $time);
    }

    /**
     * @test
     * @covers ::saveLastFailureTime
     */
    public function test_saveLastFailureTime_failure()
    {
        $key = $this->getLastFailureTimeKey();
        $time = (new DateTime('1985-10-26 01:21:00'))->getTimestamp();

        $apcStore = $this->createMock(ApcuStore::class);
        $apcStore->method('store')->willReturn(false);

        $apc = $this->getApcu($apcStore);

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Failed to set the last failure time.');
        $apc->saveLastFailureTime($key, $time);
    }

    /**
     * @test
     * @covers ::loadLastFailureTime
     */
    public function test_loadLastFailureTime_success()
    {
        $key = $this->getLastFailureTimeKey();
        $time = (new DateTime('1985-10-26 01:21:00'))->getTimestamp();

        $apcStore = $this->createMock(ApcuStore::class);
        $apcStore->expects($this->once())->method('fetch')
            ->with($key)->willReturn($time);

        $apc = $this->getApcu($apcStore);

        $this->assertSame(
            $time,
            $apc->loadLastFailureTime($key)
        );
    }

    /**
     * @test
     * @covers ::loadLastFailureTime
     */
    public function test_loadLastFailureTime_notFound()
    {
        $key = $this->getLastFailureTimeKey();

        $apcStore = $this->createMock(ApcuStore::class);
        $apcStore->expects($this->once())->method('fetch')
            ->with($key)->willReturn(false);

        $apc = $this->getApcu($apcStore);

        $this->assertNull($apc->loadLastFailureTime($key));
    }

    /**
     * @test
     * @covers ::saveStatus
     * @covers ::loadStatus
     * @covers ::reset
     */
    public function test_saveStatus_loadStatus_reset()
    {
        $key = $this->getStatusKey();
        $apc = $this->getApcu();

        $this->assertSame(Ganesha::STATUS_CALMED_DOWN, $apc->loadStatus($key));
        $apc->saveStatus($key, Ganesha::STATUS_TRIPPED);
        $this->assertSame(Ganesha::STATUS_TRIPPED, $apc->loadStatus($key));
        $apc->reset();
        $this->assertSame(Ganesha::STATUS_CALMED_DOWN, $apc->loadStatus($key));
    }

    /**
     * @test
     * @covers ::saveStatus
     */
    public function test_saveStatus_success()
    {
        $key = $this->getStatusKey();
        $status = Ganesha::STATUS_TRIPPED;

        $apcStore = $this->createMock(ApcuStore::class);
        $apcStore->expects($this->once())->method('store')
            ->with($key, $status)->willReturn(true);

        $apc = $this->getApcu($apcStore);
        $apc->saveStatus($key, $status);
    }

    /**
     * @test
     * @covers ::saveStatus
     */
    public function test_saveStatus_failure()
    {
        $key = $this->getStatusKey();
        $status = Ganesha::STATUS_TRIPPED;

        $apcStore = $this->createMock(ApcuStore::class);
        $apcStore->method('store')->willReturn(false);

        $apc = $this->getApcu($apcStore);

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Failed to set the status.');
        $apc->saveStatus($key, $status);
    }

    /**
     * @test
     * @covers ::loadStatus
     */
    public function test_loadStatus_success()
    {
        $key = $this->getStatusKey();
        $status = Ganesha::STATUS_TRIPPED;

        $apcStore = $this->createMock(ApcuStore::class);
        $apcStore->expects($this->once())->method('fetch')
            ->will($this->returnCallback(
                function ($key, &$success) use ($status) {
                    $success = true;
                    return $status;
                }
            ));
        $apcStore->expects($this->never())->method('exists');
        $apcStore->expects($this->never())->method('store');

        $apc = $this->getApcu($apcStore);
        $apc->loadStatus($key);
    }

    /**
     * @test
     * @covers ::loadStatus
     */
    public function test_loadStatus_notFound()
    {
        $key = $this->getStatusKey();

        $apcStore = $this->createMock(ApcuStore::class);
        $apcStore->expects($this->once())->method('fetch')
            ->will($this->returnCallback(
                function ($key, &$success) {
                    $success = false;
                    return false;
                }
            ));
        $apcStore->expects($this->once())->method('exists')
            ->with($key)->willReturn(false);
        $apcStore->expects($this->once())->method('store')
            ->with($key, Ganesha::STATUS_CALMED_DOWN, $this->anything())
            ->willReturn(true);

        $apc = $this->getApcu($apcStore);
        $this->assertSame(
            Ganesha::STATUS_CALMED_DOWN,
            $apc->loadStatus($key)
        );
    }

    /**
     * @test
     * @covers ::loadStatus
     */
    public function test_loadStatus_failure()
    {
        $key = $this->getStatusKey();

        $apcStore = $this->createMock(ApcuStore::class);
        $apcStore->expects($this->once())->method('fetch')
            ->will($this->returnCallback(
                function ($key, &$success) {
                    $success = false;
                    return false;
                }
            ));
        $apcStore->expects($this->once())->method('exists')
            ->with($key)->willReturn(true);
        $apcStore->expects($this->never())->method('store');

        $apc = $this->getApcu($apcStore);

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Failed to load the status.');
        $apc->loadStatus($key);
    }

    /**
     * @test
     * @covers ::reset
     */
    public function test_reset()
    {
        // Build a config with regex special characters to validate quoting
        $expectPattern = '/^\\\\.+(\\^|\\$|\\.|\\[|\\])$/';
        $storageKeys = $this->getMockForAbstractClass(StorageKeysInterface::class);
        $storageKeys->method('prefix')->willReturn('\\');
        $storageKeys->method('success')->willReturn('^');
        $storageKeys->method('failure')->willReturn('$');
        $storageKeys->method('rejection')->willReturn('.');
        $storageKeys->method('lastFailureTime')->willReturn('[');
        $storageKeys->method('status')->willReturn(']');

        $iterator = $this->createMock(APCuIterator::class);

        $apcStore = $this->createMock(ApcuStore::class);
        $apcStore->expects($this->once())->method('getIterator')
            ->with($expectPattern)->willReturn($iterator);
        $apcStore->expects($this->once())->method('delete')
            ->with($this->identicalTo($iterator));

        $apc = $this->getApcu($apcStore, $storageKeys);
        $apc->reset();
    }

    public function provide_keyRegex()
    {
        return [
            'empty' => ['', 0],
            'random words' => ['x y z', 0],
            'missing suffix' => ['^ ServiceName', 0],
            'missing prefix' => ['ServiceName s', 0],
            'missing service name' => ['^  s', 0],
            'success' => ['^ !@# s', 1],
            'failure' => ['^ ServiceName f', 1],
            'rejection' => ['^ ServiceName r', 1],
            'lastFailureTime' => ['^ ServiceName $', 1],
            'status' => ['^ ServiceName /', 1],
        ];
    }

    /**
     * @test
     * @covers ::reset
     * @dataProvider provide_keyRegex
     */
    public function test_keyRegex(string $key, int $expectMatchResult)
    {
        // self::EXPECT_KEY_REGEX is validated by test_reset()
        $this->assertSame(
            $expectMatchResult,
            preg_match(self::EXPECT_KEY_REGEX, $key),
            sprintf(
                'preg_match("%s", "%s") !== %d',
                self::EXPECT_KEY_REGEX,
                $key,
                $expectMatchResult
            )
        );
    }

    private function getApcu(
        ?ApcuStore $apcStore = null,
        ?StorageKeysInterface $storageKeys = null
    ): Apcu {
        $apc = new Apcu($apcStore);
        $context = new Ganesha\Context(
            Ganesha\Strategy\Count::class,
            $apc,
            $this->getConfiguration($storageKeys)
        );
        $apc->setContext($context);
        return $apc;
    }

    private function getConfiguration(?StorageKeysInterface $storageKeys = null)
    {
        $configuration = $this->createMock(Configuration::class);
        $configuration->method('storageKeys')
            ->willReturn($storageKeys ?? $this->getStorageKeys());
        return $configuration;
    }

    private function getStorageKeys()
    {
        $storageKeys = $this->getMockForAbstractClass(StorageKeysInterface::class);
        $storageKeys->method('prefix')->willReturn('ganesha_');
        $storageKeys->method('success')->willReturn('_success');
        $storageKeys->method('failure')->willReturn('_failure');
        $storageKeys->method('rejection')->willReturn('_rejection');
        $storageKeys->method('lastFailureTime')->willReturn('_last_failure_time');
        $storageKeys->method('status')->willReturn('_status');
        return $storageKeys;
    }

    private function getSuccessKey()
    {
        return sprintf('ganesha_%s_success', $this->getName());
    }

    private function getLastFailureTimeKey()
    {
        return sprintf('ganesha_%s_last_failure_time', $this->getName());
    }

    private function getStatusKey()
    {
        return sprintf('ganesha_%s_status', $this->getName());
    }
}
