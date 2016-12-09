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
    public function isClosed()
    {
        return $this->failureCount < $this->failureThreshold;
    }
}
