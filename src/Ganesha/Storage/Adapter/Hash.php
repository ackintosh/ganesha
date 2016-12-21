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
     * @var float[]
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
     * @return void
     */
    public function save($serviceName, $count)
    {
        $this->failureCount[$serviceName] = $count;
    }

    /**
     * @param  string $serviceName
     * @return void
     */
    public function increment($serviceName)
    {
        $this->save($serviceName, $this->load($serviceName) + 1);
    }

    /**
     * @param  string $serviceName
     * @return void
     */
    public function decrement($serviceName)
    {
        $this->save($serviceName, $this->load($serviceName) - 1);
    }

    /**
     * sets last failure time
     *
     * @param  string $serviceName
     * @param  float  $lastFailureTime
     * @return void
     */
    public function saveLastFailureTime($serviceName, $lastFailureTime)
    {
        $this->lastFailureTime[$serviceName] = $lastFailureTime;
    }

    /**
     * returns last failure time
     *
     * @return float | null
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
            return Ganesha::STATUS_CLOSE;
        }

        return $this->status[$serviceName];
    }
}
