<?php
namespace Ackintosh;

use Ackintosh\Ganesha\Exception\StorageException;

class Ganesha
{
    /**
     * @var \Ackintosh\Ganesha\StrategyInterface
     */
    private $strategy;

    /**
     * @var callable
     */
    private $behaviorOnTrip;

    /**
     * @var callable
     */
    private $behaviorOnCalmedDown;

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
     * @param \Ackintosh\Ganesha\StrategyInterface $strategy
     */
    public function __construct($strategy)
    {
        $this->strategy = $strategy;
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
     * sets behavior which will be invoked when Ganesha calmed down
     *
     * @param  callable $behavior
     * @return void
     */
    public function setBehaviorOnCalmedDown($behavior)
    {
        $this->behaviorOnCalmedDown = $behavior;
    }

    /**
     * records failure
     *
     * @return void
     */
    public function failure($serviceName)
    {
        try {
            if ($this->strategy->recordFailure($serviceName) === self::STATUS_TRIPPED) {
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
    public function success($serviceName)
    {
        try {
            if ($this->strategy->recordSuccess($serviceName) === self::STATUS_CALMED_DOWN) {
                $this->triggerBehaviorOnCalmedDown($serviceName);
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
            return $this->strategy->isAvailable($serviceName);
        } catch (StorageException $e) {
            $this->triggerBehaviorOnStorageError('failed to execute isAvailable : ' . $e->getMessage());
            // fail-silent
            return true;
        }
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
     * @param  string $serviceName
     * @return void
     */
    private function triggerBehaviorOnCalmedDown($serviceName)
    {
        if (is_null($this->behaviorOnCalmedDown)) {
            return;
        }

        call_user_func($this->behaviorOnCalmedDown, $serviceName);
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

    /**
     * enable
     *
     * @return void
     */
    public static function enable()
    {
        self::$disabled = false;
    }
}
