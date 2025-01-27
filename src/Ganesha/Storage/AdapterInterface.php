<?php

namespace Ackintosh\Ganesha\Storage;

use Ackintosh\Ganesha\Configuration;
use Ackintosh\Ganesha\Context;

interface AdapterInterface
{
    /**
     * Returns whether the adapter supports counting strategy
     */
    public function supportCountStrategy(): bool;

    /**
     * Returns whether the adapter supports rating strategy
     */
    public function supportRateStrategy(): bool;

    public function setContext(Context $context): void;

    /**
     * @deprecated This method will be removed in the next major release. Please use `setContext` instead.
     */
    public function setConfiguration(Configuration $configuration): void;

    public function load(string $service): int;

    public function save(string $service, int $count): void;

    public function increment(string $service): void;

    /**
     * Decrement failure count
     *
     * If the operation would decrease the value below 0, the new value must be 0.
     */
    public function decrement(string $service): void;

    /**
     * Sets last failure time
     */
    public function saveLastFailureTime(string $service, int $lastFailureTime): void;

    /**
     * Returns last failure time
     *
     * @return int | null
     */
    public function loadLastFailureTime(string $service);

    /**
     * Sets status
     */
    public function saveStatus(string $service, int $status): void;

    /**
     * Returns status
     */
    public function loadStatus(string $service): int;

    /**
     * Resets all counts
     */
    public function reset(): void;
}
