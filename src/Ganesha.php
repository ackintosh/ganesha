<?php
namespace Ackintosh;

use Ackintosh\Ganesha\Storage;

class Ganesha
{
    /**
     * @var Strage
     */
    private $storage;

    /**
     * @var int
     */
    private $failureThreshold;

    /**
     * @var float
     */
    private $resetTimeout = 0.1;

    /**
     * Ganesha constructor.
     *
     * @param int $failureThreshold
     */
    public function __construct($failureThreshold = 10)
    {
        $this->storage = new Storage();
        $this->failureThreshold = $failureThreshold;
    }

    /**
     * records failure
     *
     * @return void
     */
    public function recordFailure($serviceName)
    {
        $this->storage->setLastFailureTime(microtime(true));
        $this->storage->incrementFailureCount($serviceName);
    }

    /**
     * records success
     *
     * @return void
     */
    public function recordSuccess($serviceName)
    {
        if ($this->storage->getFailureCount($serviceName) > 0) {
            $this->storage->decrementFailureCount($serviceName);
        }
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
        return $this->storage->getFailureCount($serviceName) < $this->failureThreshold;
    }

    /**
     * @return bool
     */
    private function isHalfOpen($serviceName)
    {
        if (is_null($lastFailureTime = $this->storage->getLastFailureTime())) {
            return false;
        }

        if ((microtime(true) - $lastFailureTime) > $this->resetTimeout) {
            $this->storage->setFailureCount($serviceName, $this->failureThreshold);
            return true;
        }

        return false;
    }
}
