<?php
namespace Ackintosh\Ganesha\Traits;

use Ackintosh\Ganesha\Builder;
use Ackintosh\Ganesha\Storage\Adapter\Redis;
use PHPUnit\Framework\TestCase;

class BuildGaneshaTest extends TestCase
{
    /**
     * @test
     * @expectedException \LogicException
     * @expectedExceptionMessage adapter is required
     */
    public function validateThrowsExceptionWhenRequirementsAreNotSatisfied() {
        Builder::withRateStrategy()
            ->build();
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Ackintosh\Ganesha\Storage\Adapter\Redis doesn't support expected Strategy: supportCountStrategy
     */
    public function validateThrowsExceptionWhenAdapterRequirementsAreNotSatisfied() {
        if (!\extension_loaded('redis')) {
            self::markTestSkipped('No ext-redis present');
        }

        $r = new \Redis();
        $r->connect(
            getenv('GANESHA_EXAMPLE_REDIS') ? getenv('GANESHA_EXAMPLE_REDIS') : 'localhost'
        );
        $r->flushAll();

        Builder::withCountStrategy()
            ->adapter(new Redis($r)) // Redis adapter doesn't support count strategy
            ->failureCountThreshold(10)
            ->intervalToHalfOpen(10)
            ->build();
    }
}