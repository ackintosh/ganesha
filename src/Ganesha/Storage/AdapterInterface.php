<?php
namespace Ackintosh\Ganesha\Storage;

use Ackintosh\Ganesha\Configuration;

interface AdapterInterface
{
    /**
     * Returns returns whether the adapter supports counting strategy
     * @return bool
     */
    public function supportCountStrategy();

    /**
     * Returns returns whether the adapter supports rating strategy
     * @return bool
     */
    public function supportRateStrategy();

    /**
     * @param Configuration $configuration
     * @return void
     */
    public function setConfiguration(Configuration $configuration);

    /**
     * @param  string $service
     * @return int
     */
    public function load($service);

    /**
     * @param  string $service
     * @param  int    $count
     * @return void
     */
    public function save($service, $count);

    /**
     * @param  string $service
     * @return void
     */
    public function increment($service);

    /**
     * decrement failure count
     *
     * If the operation would decrease the value below 0, the new value must be 0.
     *
     * @param  string $service
     * @return void
     */
    public function decrement($service);

    /**
     * sets last failure time
     *
     * @param  string $service
     * @param  int    $lastFailureTime
     * @return void
     */
    public function saveLastFailureTime($service, $lastFailureTime);

    /**
     * returns last failure time
     *
     * @return int | null
     */
    public function loadLastFailureTime($service);

    /**
     * sets status
     *
     * @param  string $service
     * @param  int    $status
     * @return void
     */
    public function saveStatus($service, $status);

    /**
     * returns status
     *
     * @param  string $service
     * @return int
     */
    public function loadStatus($service);

    /**
     * resets all counts
     *
     * @return void
     */
    public function reset();
}
