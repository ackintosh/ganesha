<?php
namespace Ackintosh\Ganesha\Storage;

use Ackintosh\Ganesha\Configuration;
use Ackintosh\Ganesha\Context;

interface AdapterInterface
{
    /**
     * Returns returns whether the adapter supports counting strategy
     * @return bool
     */
    public function supportCountStrategy(): bool ;

    /**
     * Returns returns whether the adapter supports rating strategy
     * @return bool
     */
    public function supportRateStrategy(): bool ;

    /**
     * @param Context $context
     * @return void
     */
    public function setContext(Context $context): void;

    /**
     * @deprecated This method will be removed in the next major release. Please use `setContext` instead.
     * @param Configuration $configuration
     * @return void
     */
    public function setConfiguration(Configuration $configuration): void;

    /**
     * @param  string $service
     * @return int
     */
    public function load(string $service): int;

    /**
     * @param  string $service
     * @param  int    $count
     * @return void
     */
    public function save(string $service, int $count): void;

    /**
     * @param  string $service
     * @return void
     */
    public function increment(string $service): void;

    /**
     * decrement failure count
     *
     * If the operation would decrease the value below 0, the new value must be 0.
     *
     * @param  string $service
     * @return void
     */
    public function decrement(string $service): void;

    /**
     * sets last failure time
     *
     * @param  string $service
     * @param  int    $lastFailureTime
     * @return void
     */
    public function saveLastFailureTime(string $service, int $lastFailureTime): void;

    /**
     * returns last failure time
     *
     * @param  string $service
     * @return int | null
     */
    public function loadLastFailureTime(string $service);

    /**
     * sets status
     *
     * @param  string $service
     * @param  int    $status
     * @return void
     */
    public function saveStatus(string $service, int $status): void;

    /**
     * returns status
     *
     * @param  string $service
     * @return int
     */
    public function loadStatus(string $service): int;

    /**
     * resets all counts
     *
     * @return void
     */
    public function reset(): void;
}
