<?php
namespace Ackintosh\Ganesha;

use Ackintosh\Ganesha\Storage\AdapterInterface;

class Configuration
{
    /**
     * @var string
     */
    private $strategyClass = '\Ackintosh\Ganesha\Strategy\Absolute';

    /**
     * @var AdapterInterface
     */
    private $adapter;

    /**
     * @var callable
     */
    private $adapterSetupFunction;

    /**
     * @var int
     */
    private $failureThreshold = 10;

    /**
     * @var int
     */
    private $intervalToHalfOpen = 5;

    /**
     * @var int
     */
    private $countTTL = 60;

    /**
     * @var callable
     */
    private $behaviorOnStorageError;

    /**
     * @var callable
     */
    private $behaviorOnTrip;

    /**
     * @throws \LogicException
     * @return void
     */
    public function validate()
    {
        if (!$this->adapter instanceof AdapterInterface && is_null($this->adapterSetupFunction)) {
            throw new \LogicException();
        }
    }

    public function setStrategyClass($strategyClass)
    {
        $this->strategyClass = $strategyClass;
    }

    public function getStrategyClass()
    {
        return $this->strategyClass;
    }

    /**
     * @param int $failureThreshold
     * @return void
     */
    public function setFailureThreshold($failureThreshold)
    {
        $this->failureThreshold = $failureThreshold;
    }

    /**
     * @return int
     */
    public function getFailureThreshold()
    {
        return $this->failureThreshold;
    }

    /**
     * @param AdapterInterface $adapter
     * @return
     */
    public function setAdapter(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * @return AdapterInterface
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * @param  callable $function
     * @return void
     */
    public function setAdapterSetupFunction($function)
    {
        $this->adapterSetupFunction = $function;
    }

    /**
     * @return callable|\Closure
     */
    public function getAdapterSetupFunction()
    {
        if ($adapter = $this->adapter) {
            return function () use ($adapter) {
                return $adapter;
            };
        }

        return $this->adapterSetupFunction;
    }

    /**
     * @param int $interval
     * @return void
     */
    public function setIntervalToHalfOpen($interval)
    {
        $this->intervalToHalfOpen = $interval;
    }

    /**
     * @return int
     */
    public function getIntervalToHalfOpen()
    {
        return $this->intervalToHalfOpen;
    }

    /**
     * @param $countTTL
     * @return void
     */
    public function setCountTTL($countTTL)
    {
        $this->countTTL = $countTTL;
    }

    /**
     * @return int
     */
    public function getCountTTL()
    {
        return $this->countTTL;
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
     * @return callable
     */
    public function getBehaviorOnStorageError()
    {
        return $this->behaviorOnStorageError;
    }

    /**
     * @param  callable $behavior
     * @return void
     */
    public function setBehaviorOnTrip($behavior)
    {
        $this->behaviorOnTrip = $behavior;
    }

    /**
     * @return callable
     */
    public function getBehaviorOnTrip()
    {
        return $this->behaviorOnTrip;
    }
}
