<?php
namespace Ackintosh\Ganesha;

use LogicException;

interface StrategyInterface
{
    /**
     * @param array $params
     * @throws LogicException
     */
    public static function validate(array $params): void;

    /**
     * @param Configuration $configuration
     * @return mixed
     */
    public static function create(Configuration $configuration): StrategyInterface;

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
