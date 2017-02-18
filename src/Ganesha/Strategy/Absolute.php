<?php
namespace Ackintosh\Ganesha\Strategy;

use Ackintosh\Ganesha;
use Ackintosh\Ganesha\Exception\StorageException;

class Absolute
{
    /**
     * @var int
     */
    private $failureThreshold;

    /**
     * @var int
     */
    private $intervalToHalfOpen;

    /**
     * @var \Ackintosh\Ganesha\Storage
     */
    private $storage;

    public function setFailureThreshold($threshold)
    {
        $this->failureThreshold = $threshold;
    }

    public function setIntervalToHalfOpen($interval)
    {
        $this->intervalToHalfOpen = $interval;
    }

    /**
     * @param \Ackintosh\Ganesha\Storage $storage
     */
    public function setStorage($storage)
    {
        $this->storage = $storage;
    }

    /**
     * @return void
     */
    public function recordFailure($serviceName)
    {
        $this->storage->setLastFailureTime($serviceName, time());
        $this->storage->incrementFailureCount($serviceName);

        if ($this->storage->getFailureCount($serviceName) >= $this->failureThreshold
            && $this->storage->getStatus($serviceName) === Ganesha::STATUS_CALMED_DOWN
        ) {
            $this->storage->setStatus($serviceName, Ganesha::STATUS_TRIPPED);
            return Ganesha::STATUS_TRIPPED;
        }
    }

    /**
     * @return void
     */
    public function recordSuccess($serviceName)
    {
        $this->storage->decrementFailureCount($serviceName);

        if ($this->storage->getFailureCount($serviceName) === 0
            && $this->storage->getStatus($serviceName) === Ganesha::STATUS_TRIPPED
        ) {
            $this->storage->setStatus($serviceName, Ganesha::STATUS_CALMED_DOWN);
        }
    }

    /**
     * @return bool
     */
    public function isAvailable($serviceName)
    {
        try {
            return $this->isClosed($serviceName) || $this->isHalfOpen($serviceName);
        } catch (StorageException $e) {
            throw $e;
        }
    }

    /**
     * @return bool
     * @throws StorageException
     */
    private function isClosed($serviceName)
    {
        try {
            return $this->storage->getFailureCount($serviceName) < $this->failureThreshold;
        } catch (StorageException $e) {
            throw $e;
        }
    }

    /**
     * @return bool
     * @throws StorageException
     */
    private function isHalfOpen($serviceName)
    {
        if (is_null($lastFailureTime = $this->storage->getLastFailureTime($serviceName))) {
            return false;
        }

        if ((time() - $lastFailureTime) > $this->intervalToHalfOpen) {
            $this->storage->setFailureCount($serviceName, $this->failureThreshold);
            $this->storage->setLastFailureTime($serviceName, time());
            return true;
        }

        return false;
    }
}
