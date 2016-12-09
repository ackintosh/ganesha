<?php
namespace Ackintosh;

class Ganesha
{
    /**
     * @var int
     */
    private $failureCount = 0;

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
    public function recordFailure()
    {
        $this->failureCount++;
    }

    /**
     * records success
     *
     * @return void
     */
    public function recordSuccess()
    {
        if ($this->failureCount > 0) {
            $this->failureCount--;
        }
    }

    /**
     * @return bool
     */
    public function isAvailable()
    {
        return $this->isClosed() || $this->isHalfOpen();
    }

    /**
     * @return bool
     */
    private function isClosed()
    {
        return $this->failureCount < $this->failureThreshold;
    }

    /**
     * @return bool
     */
    private function isHalfOpen()
    {
        if (is_null($this->lastFailureTime)) {
            return false;
        }

        if ((microtime(true) - $this->lastFailureTime) > $this->resetTimeout) {
            $this->failureCount = $this->failureThreshold;
            return true;
        }

        return false;
    }
}
