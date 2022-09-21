<?php
namespace Ackintosh\Ganesha;

use Ackintosh\Ganesha\Storage\AdapterInterface;

interface StrategyInterface
{
    /**
     * @param AdapterInterface $adapter
     * @param Configuration $configuration
     * @return mixed
     */
    public static function create(AdapterInterface $adapter, Configuration $configuration): StrategyInterface;

    /**
     * @param string $service
     * @return int
     */
    public function recordSuccess(string $service): ?int;

    /**
     * @param string $service
     * @return int
     */
    public function recordFailure(string $service): int;

    /**
     * @param string $service
     * @return bool
     */
    public function isAvailable(string $service): bool;

    /**
     * @return void
     */
    public function reset(): void;
}
