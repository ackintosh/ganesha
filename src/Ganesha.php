<?php
namespace Ackintosh;

class Ganesha
{
    /**
     * @var int[]
     */
    private $failureCount = [];

    /**
     * @var int
     */
    private $failureThreshold;

    /**
     * @var float
     */
    private $resetTimeout = 0.1;

    /**
     * @var float
     */
    private $lastFailureTime;

    /**
     * Ganesha constructor.
     *
     * @param int $failureThreshold
     */
    public function __construct($failureThreshold = 10)
    {
        $this->failureThreshold = $failureThreshold;
    }

    /**
     * records failure
     *
     * @return void
     */
    public function recordFailure($serviceName)
    {
        $this->incrementFailureCount($serviceName);
    }

    /**
     * records success
     *
     * @return void
     */
    public function recordSuccess($serviceName)
    {
        if ($this->getFailureCount($serviceName) > 0) {
            $this->failureCount[$serviceName]--;
        }
    }

    /**
     * returns failure count
     *
     * @param  string $serviceName
     * @return int
     */
    private function getFailureCount($serviceName)
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
    private function incrementFailureCount($serviceName)
    {
        $this->failureCount[$serviceName] = $this->getFailureCount($serviceName) + 1;
    }

    /**
     * @return bool
     */
    public function isAvailable($serviceName)
    {
        return $this->isClosed($serviceName) || $this->isHalfOpen($serviceName);
    }

    /**
     * @return bool
     */
    private function isClosed($serviceName)
    {
        return $this->getFailureCount($serviceName) < $this->failureThreshold;
    }

    /**
     * @return bool
     */
    private function isHalfOpen($serviceName)
    {
        if (is_null($this->lastFailureTime)) {
            return false;
        }

        if ((microtime(true) - $this->lastFailureTime) > $this->resetTimeout) {
            $this->failureCount[$serviceName] = $this->failureThreshold;
            return true;
        }

        return false;
    }
}
