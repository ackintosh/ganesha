<?php

namespace Ackintosh\Ganesha;

use Ackintosh\Ganesha;
use Ackintosh\Ganesha\Storage\Adapter\Memcached;
use Ackintosh\Ganesha\Storage\Adapter\Redis;
use PHPUnit\Framework\TestCase;

class StorageTest extends TestCase
{
    /**
     * @test
     */
    public function savesStatus()
    {
        if (!\extension_loaded('memcached')) {
            self::markTestSkipped('No ext-memcached present');
        }

        $m = new \Memcached();
        $m->addServer(
            getenv('GANESHA_EXAMPLE_MEMCACHED') ? getenv('GANESHA_EXAMPLE_MEMCACHED') : 'localhost',
            11211
        );
        $storage = new Storage(new Memcached($m), new Ganesha\Storage\StorageKeys(), null);

        $service = 'test';
        $this->assertSame($storage->getStatus($service), Ganesha::STATUS_CALMED_DOWN);
        $storage->setStatus($service, Ganesha::STATUS_TRIPPED);
        $this->assertSame($storage->getStatus($service), Ganesha::STATUS_TRIPPED);
    }

    /**
     * @test
     */
    public function getLastFailureTimeWithSlidingTimeWindow()
    {
        if (!\extension_loaded('redis')) {
            self::markTestSkipped('No ext-redis present');
        }

        $r = new \Redis();
        $r->connect(
            getenv('GANESHA_EXAMPLE_REDIS') ? getenv('GANESHA_EXAMPLE_REDIS') : 'localhost'
        );
        $r->flushAll();

        $redisAdapter = new Redis($r);
        $context = new Ganesha\Context(
            Ganesha\Strategy\Rate::class,
            $redisAdapter,
            new Configuration(['timeWindow' => 3])
        );
        $redisAdapter->setContext($context);
        $storage = new Storage($redisAdapter, new Ganesha\Storage\StorageKeys(), null);

        $service = 'test';
        $storage->incrementFailureCount($service);

        $this->assertNotNull($storage->getLastFailureTime($service));
    }
}
