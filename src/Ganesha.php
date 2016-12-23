<?php
namespace Ackintosh;

use Ackintosh\Ganesha\Storage;
use Ackintosh\Ganesha\Storage\AdapterInterface;

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
    public function __construct($failureThreshold)
    {
        $this->failureThreshold = $failureThreshold;
    }

    /**
     * @param  callable $setupFunction
     * @return void
     */
    public function setupStorage(callable $setupFunction)
    {
        $this->storage = new Storage(call_user_func($setupFunction));
    }

    /**
     * records failure
     *
     * @return void
     */
    public function recordFailure($serviceName)
    {
        $this->storage->setLastFailureTime($serviceName, microtime(true));
        $this->storage->incrementFailureCount($serviceName);

        if ($this->storage->getFailureCount($serviceName) >= $this->failureThreshold
            && $this->storage->getStatus($serviceName) === self::STATUS_CLOSE
        ) {
            $this->storage->setStatus($serviceName, self::STATUS_OPEN);
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
        if ($this->storage->getFailureCount($serviceName) > 0) {
            $this->storage->decrementFailureCount($serviceName);
        }

        if ($this->storage->getFailureCount($serviceName) === 0
            && $this->storage->getStatus($serviceName) !== self::STATUS_OPEN
        ) {
            $this->storage->setStatus($serviceName, self::STATUS_CLOSE);
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
