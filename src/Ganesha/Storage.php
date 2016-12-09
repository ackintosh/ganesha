<?php
namespace Ackintosh\Ganesha;

class Storage
{
    /**
     * @var int[]
     */
    private $failureCount = [];

    /**
     * returns failure count
     *
     * @param  string $serviceName
     * @return int
     */
    public function getFailureCount($serviceName)
    {
        if (!isset($this->failureCount[$serviceName])) {
            $this->failureCount[$serviceName] = 0;
        }

        return $this->failureCount[$serviceName];
    }

    /**
     * increments failure count
     *
     * @param  string $serviceName
     * @return void
     */
    public function incrementFailureCount($serviceName)
    {
        $this->failureCount[$serviceName] = $this->getFailureCount($serviceName) + 1;
    }

    /**
     * decrements failure count
     *
     * @param  string $serviceName
     * @return void
     */
    public function decrementFailureCount($serviceName)
    {
        $this->failureCount[$serviceName] = $this->getFailureCount($serviceName) - 1;
    }

    /**
     * sets failure count
     *
     * @param $serviceName
     * @param $failureCount
     */
    public function setFailureCount($serviceName, $failureCount)
    {
        $this->failureCount[$serviceName] = $failureCount;
    }
}
