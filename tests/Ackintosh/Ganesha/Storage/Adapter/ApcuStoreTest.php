<?php
namespace Ackintosh\Ganesha\Storage\Adapter;

use APCuIterator;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Ackintosh\Ganesha\Storage\Adapter\ApcuStore
 */
class ApcuStoreTest extends TestCase
{
    protected function tearDown()
    {
        apcu_clear_cache();
        parent::tearDown();
    }

    /**
     * @test
     * @covers ::dec
     */
    public function test_dec()
    {
        $key = $this->getName();
        $store = new ApcuStore();

        $this->assertSame(-1, $store->dec($key));
        $this->assertSame(-2, $store->dec($key));
        $this->assertSame(-4, $store->dec($key, 2, $success, 86400));
        $this->assertTrue($success);
    }

    /**
     * @test
     * @covers ::delete
     */
    public function test_delete()
    {
        $key = $this->getName();
        $value = __METHOD__;
        $store = new ApcuStore();

        apcu_store($key, $value);
        $store->delete($key);
        $this->assertFalse(apcu_fetch($key));
    }

    /**
     * @test
     * @covers ::exists
     */
    public function test_exists()
    {
        $key = $this->getName();
        $value = __METHOD__;
        $store = new ApcuStore();

        $this->assertFalse($store->exists($key));
        apcu_store($key, $value);
        $this->assertTrue($store->exists($key));
    }

    /**
     * @test
     * @covers ::fetch
     */
    public function test_fetch()
    {
        $key = $this->getName();
        $value = __METHOD__;
        $store = new ApcuStore();

        $result = $store->fetch($key, $success);
        $this->assertFalse($result);
        $this->assertFalse($success);

        apcu_store($key, $value);

        $result = $store->fetch($key, $success);
        $this->assertSame($value, $result);
        $this->assertTrue($success);
    }

    /**
     * @test
     * @covers ::inc
     */
    public function test_inc()
    {
        $key = $this->getName();
        $store = new ApcuStore();

        $this->assertSame(1, $store->inc($key));
        $this->assertSame(2, $store->inc($key));
        $this->assertSame(4, $store->inc($key, 2, $success, 86400));
        $this->assertTrue($success);
    }

    /**
     * @test
     * @covers ::store
     */
    public function test_store()
    {
        $key = $this->getName();
        $value = __METHOD__;
        $store = new ApcuStore();

        $this->assertFalse(apcu_exists($key));

        $store->store($key, $value);

        $this->assertSame($value, apcu_fetch($key));
    }

    public function provide_getIterator()
    {
        return [
            'match all' => [null, 100],
            'match even numbers' => ['/[02468]$/', 50],
            'match single digits' => ['/ \d$/', 9],
        ];
    }

    /**
     * @test
     * @covers ::getIterator
     * @dataProvider provide_getIterator
     */
    public function test_getIterator(?string $pattern, int $expectCount)
    {
        $name = $this->getName();
        $keys = array_map(
            function ($index) use ($name) {
                return sprintf('%s %d', $name, $index);
            },
            range(1, 100)
        );
        $store = new ApcuStore();

        apcu_store(array_flip($keys));

        $iterator = $store->getIterator($pattern);
        $this->assertInstanceOf(APCuIterator::class, $iterator);
        $this->assertCount($expectCount, $iterator);
    }
}
