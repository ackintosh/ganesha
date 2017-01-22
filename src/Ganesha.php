<?php
namespace Ackintosh;

use Ackintosh\Ganesha\Exception\StorageException;
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
    private $behaviorOnTrip;

    /**
     * @var callable
     */
    private $behaviorOnStorageError;

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
     * @var bool
     */
    private static $disabled = false;

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
    public function setupStorage($setupFunction, $counterTTL)
    {
        $this->storage = new Storage(call_user_func($setupFunction), $counterTTL);
    }

    /**
     * @param  int $interval
     * @return void
     */
    public function setIntervalToHalfOpen($interval)
    {
        $this->intervalToHalfOpen = $interval;
    }

    /**
     * @param  callable $loggingBehavior
     * @return void
     */
    public function setBehaviorOnStorageError($behavior)
    {
        $this->behaviorOnStorageError = $behavior;
    }

    /**
     * sets behavior which will be invoked when Ganesha trips
     *
     * @param  callable $behavior
     * @return void
     */
    public function setBehaviorOnTrip($behavior)
    {
        $this->behaviorOnTrip = $behavior;
    }

    /**
     * records failure
     *
     * @return void
     */
    public function recordFailure($serviceName)
    {
        try {
            $this->storage->setLastFailureTime($serviceName, time());
            $this->storage->incrementFailureCount($serviceName);

            if ($this->storage->getFailureCount($serviceName) >= $this->failureThreshold
                && $this->storage->getStatus($serviceName) === self::STATUS_CALMED_DOWN
            ) {
                $this->storage->setStatus($serviceName, self::STATUS_TRIPPED);
                $this->triggerBehaviorOnTrip($serviceName);
            }
        } catch (StorageException $e) {
            $this->triggerBehaviorOnStorageError('failed to record failure : ' . $e->getMessage());
        }
    }

    /**
     * records success
     *
     * @return void
     */
    public function recordSuccess($serviceName)
    {
        try {
            $this->storage->decrementFailureCount($serviceName);

            if ($this->storage->getFailureCount($serviceName) === 0
                && $this->storage->getStatus($serviceName) === self::STATUS_TRIPPED
            ) {
                $this->storage->setStatus($serviceName, self::STATUS_CALMED_DOWN);
            }
        } catch (StorageException $e) {
            $this->triggerBehaviorOnStorageError('failed to record success : ' . $e->getMessage());
        }
    }

    /**
     * @return bool
     */
    public function isAvailable($serviceName)
    {
        if (self::$disabled) {
            return true;
        }

        try {
            return $this->isClosed($serviceName) || $this->isHalfOpen($serviceName);
        } catch (StorageException $e) {
            $this->triggerBehaviorOnStorageError('failed to execute isAvailable : ' . $e->getMessage());
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

    /**
     * @param  string  $message
     * @return void
     */
    private function triggerBehaviorOnStorageError($message)
    {
        if (is_null($this->behaviorOnStorageError)) {
            return;
        }

        call_user_func($this->behaviorOnStorageError, $message);
    }

    /**
     * @param  string $serviceName
     * @return void
     */
    private function triggerBehaviorOnTrip($serviceName)
    {
        if (is_null($this->behaviorOnTrip)) {
            return;
        }

        call_user_func($this->behaviorOnTrip, $serviceName);
    }

    /**
     * disable
     *
     * @return void
     */
    public static function disable()
    {
        self::$disabled = true;
    }
}
