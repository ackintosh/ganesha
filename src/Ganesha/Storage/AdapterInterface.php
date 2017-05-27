<?php
namespace Ackintosh\Ganesha\Storage;

interface AdapterInterface
{
    /**
     * @param  string $resource
     * @return int
     */
    public function load($resource);

    /**
     * @param  string $resource
     * @param  int    $count
     * @return void
     */
    public function save($resource, $count);

    /**
     * @param  string $resource
     * @return void
     */
    public function increment($resource);

    /**
     * decrement failure count
     *
     * If the operation would decrease the value below 0, the new value must be 0.
     *
     * @param  string $resource
     * @return void
     */
    public function decrement($resource);

    /**
     * sets last failure time
     *
     * @param  string $resource
     * @param  int    $lastFailureTime
     * @return void
     */
    public function saveLastFailureTime($resource, $lastFailureTime);

    /**
     * returns last failure time
     *
     * @return int | null
     */
    public function loadLastFailureTime($resource);

    /**
     * sets status
     *
     * @param  string $resource
     * @param  int    $status
     * @return void
     */
    public function saveStatus($resource, $status);

    /**
     * returns status
     *
     * @param  string $resource
     * @return int
     */
    public function loadStatus($resource);

    /**
     * resets all counts
     *
     * @return void
     */
    public function reset();
}
