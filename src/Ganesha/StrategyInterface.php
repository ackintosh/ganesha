<?php

namespace Ackintosh\Ganesha;

use Ackintosh\Ganesha\Storage\AdapterInterface;

interface StrategyInterface
{
    public static function create(AdapterInterface $adapter, Configuration $configuration): StrategyInterface;

    public function recordSuccess(string $service): ?int;

    public function recordFailure(string $service): int;

    public function isAvailable(string $service): bool;

    public function reset(): void;
}
