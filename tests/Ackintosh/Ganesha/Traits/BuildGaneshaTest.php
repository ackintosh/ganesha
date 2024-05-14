<?php

namespace Ackintosh\Ganesha\Traits;

use Ackintosh\Ganesha\Builder;
use Ackintosh\Ganesha\Storage\Adapter\Redis;
use PHPUnit\Framework\TestCase;

class BuildGaneshaTest extends TestCase
{
    /**
     * @test
     */
    public function validateThrowsExceptionWhenRequirementsAreNotSatisfied()
    {
        $this->expectExceptionMessage('adapter is required');
        $this->expectException(\LogicException::class);

        Builder::withRateStrategy()
            ->build();
    }

    /**
     * @test
     */
    public function validateThrowsExceptionWhenAdapterRequirementsAreNotSatisfied()
    {
        $this->expectExceptionMessage("Ackintosh\Ganesha\Storage\Adapter\Redis doesn't support expected Strategy: supportCountStrategy");
        $this->expectException(\InvalidArgumentException::class);

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
