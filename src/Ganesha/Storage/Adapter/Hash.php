<?php
namespace Ackintosh\Ganesha\Storage\Adapter;

use Ackintosh\Ganesha;

class Hash
{
    /**
     * @var int[]
     */
    private $failureCount = [];

    /**
     * @var float
     */
    private $lastFailureTime;

    /**
     * @var int
     */
    private $status = Ganesha::STATUS_CLOSE;

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
     * sets last failure time
     *
     * @param  float $lastFailureTime
     * @return void
     */
    public function saveLastFailureTime($lastFailureTime)
    {
        $this->lastFailureTime = $lastFailureTime;
    }

    /**
     * returns last failure time
     *
     * @return float | null
     */
    public function loadLastFailureTime()
    {
        return $this->lastFailureTime;
    }

    /**
     * sets status
     *
     * @param  int $status
     * @return void
     */
    public function saveStatus($status)
    {
        $this->status = $status;
    }

    /**
     * returns status
     *
     * @return int
     */
    public function loadStatus()
    {
        return $this->status;
    }
}
