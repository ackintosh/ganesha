<?php
namespace Ackintosh\Ganesha\Storage\Adapter;

use Ackintosh\Ganesha;
use Ackintosh\Ganesha\Storage\AdapterInterface;

/**
 * Can only be used for tests.
 */
class Hash implements AdapterInterface
{
    /**
     * @var int[]
     */
    private $failureCount = [];

    /**
     * @var int[]
     */
    private $lastFailureTime = [];

    /**
     * @var int[]
     */
    private $status = [];

    /**
     * @param  string $serviceName
     * @return int
     */
    public function load($serviceName)
    {
        if (!isset($this->failureCount[$serviceName])) {
            $this->failureCount[$serviceName] = 0;
        }

        return $this->failureCount[$serviceName];
    }

    /**
     * @param  string $serviceName
     * @param  int    $count
     * @param  int    $ttl
     * @return void
     */
    public function save($serviceName, $count, $ttl)
    {
        $this->failureCount[$serviceName] = $count;
    }

    /**
     * @param  string $serviceName
     * @param  int    $ttl
     * @return void
     */
    public function increment($serviceName, $ttl)
    {
        $this->save($serviceName, $this->load($serviceName) + 1, $ttl);
    }

    /**
     * @param  string $serviceName
     * @param  int    $ttl
     * @return void
     */
    public function decrement($serviceName, $ttl)
    {
        $this->save(
            $serviceName,
            ($count = $this->load($serviceName)) > 0 ? $count - 1 : 0,
            $ttl
        );
    }

    /**
     * sets last failure time
     *
     * @param  string $serviceName
     * @param  int    $lastFailureTime
     * @return void
     */
    public function saveLastFailureTime($serviceName, $lastFailureTime)
    {
        $this->lastFailureTime[$serviceName] = $lastFailureTime;
    }

    /**
     * returns last failure time
     *
     * @return int | null
     */
    public function loadLastFailureTime($serviceName)
    {
        return $this->lastFailureTime[$serviceName];
    }

    /**
     * sets status
     *
     * @param  string $serviceName
     * @param  int $status
     * @return void
     */
    public function saveStatus($serviceName, $status)
    {
        $this->status[$serviceName] = $status;
    }

    /**
     * returns status
     *
     * @param  string $serviceName
     * @return int
     */
    public function loadStatus($serviceName)
    {
        if (!isset($this->status[$serviceName])) {
            return Ganesha::STATUS_CALMED_DOWN;
        }

        return $this->status[$serviceName];
    }
}
