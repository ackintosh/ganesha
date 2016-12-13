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
     * @var callable
     */
    private $behavior;

    /**
     * @var int
     */
    const STATUS_CLOSE = 1;
    const STATUS_OPEN  = 2;

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

        if ($this->storage->getFailureCount($serviceName) >= $this->failureThreshold
            && $this->storage->getStatus() === self::STATUS_CLOSE
        ) {
            $this->storage->setStatus(self::STATUS_OPEN);
            if ($this->behavior) {
                call_user_func($this->behavior);
            }
        }
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

        if ($this->storage->getFailureCount($serviceName) === 0
            && $this->storage->getStatus() !== self::STATUS_OPEN
        ) {
            $this->storage->setStatus(self::STATUS_CLOSE);
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

    /**
     * sets behavior which will be invoked when Ganesha trips
     *
     * @param callable $callback
     */
    public function onTrip($callback)
    {
        $this->behavior = $callback;
    }
}
