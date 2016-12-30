<?php
namespace Ackintosh;

use Ackintosh\Ganesha\Storage;

class Ganesha
{
    /**
     * @var Storage
     */
    private $storage;

    /**
     * @var int
     */
    private $failureThreshold;

    /**
     * @var int
     */
    private $intervalToHalfOpen;

    /**
     * @var callable
     */
    private $behavior;

    /**
     * the status between failure count 0 and trip.
     * @var int
     */
    const STATUS_CALMED_DOWN = 1;

    /**
     * the status between trip and calm down.
     * @var int
     */
    const STATUS_TRIPPED  = 2;

    /**
     * Ganesha constructor.
     *
     * @param int $failureThreshold
     */
    public function __construct($failureThreshold)
    {
        $this->failureThreshold = $failureThreshold;
    }

    /**
     * @param  callable $setupFunction
     * @param  int      $counterTTL
     * @return void
     */
    public function setupStorage(callable $setupFunction, $counterTTL)
    {
        $this->storage = new Storage(call_user_func($setupFunction), $counterTTL);
    }

    /**
     * @param int $interval
     * @return void;
     */
    public function setIntervalToHalfOpen($interval)
    {
        $this->intervalToHalfOpen = $interval;
    }

    /**
     * records failure
     *
     * @return void
     */
    public function recordFailure($serviceName)
    {
        $this->storage->setLastFailureTime($serviceName, time());
        $this->storage->incrementFailureCount($serviceName);

        if ($this->storage->getFailureCount($serviceName) >= $this->failureThreshold
            && $this->storage->getStatus($serviceName) === self::STATUS_CALMED_DOWN
        ) {
            $this->storage->setStatus($serviceName, self::STATUS_TRIPPED);
            if ($this->behavior) {
                call_user_func($this->behavior, $serviceName);
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
        $this->storage->decrementFailureCount($serviceName);

        if ($this->storage->getFailureCount($serviceName) === 0
            && $this->storage->getStatus($serviceName) === self::STATUS_TRIPPED
        ) {
            $this->storage->setStatus($serviceName, self::STATUS_CALMED_DOWN);
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

    /**
     * sets behavior which will be invoked when Ganesha trips
     *
     * @param callable $callback
     * @throws \InvalidArgumentException
     */
    public function onTrip($callback)
    {
        if (!is_callable($callback)) {
            throw new \InvalidArgumentException(__METHOD__ . ' allows only callable.');
        }

        $this->behavior = $callback;
    }
}
