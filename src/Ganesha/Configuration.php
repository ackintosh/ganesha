<?php
namespace Ackintosh\Ganesha;

use Ackintosh\Ganesha\Storage\AdapterInterface;

class Configuration
{
    /**
     * @var AdapterInterface
     */
    private $storageAdaper;

    /**
     * @var callable
     */
    private $storageSetupFunction;

    /**
     * @var int
     */
    private $failureThreshold = 10;

    /**
     * @throws \LogicException
     * @return void
     */
    public function validate()
    {
        if (!$this->storageAdaper instanceof AdapterInterface && is_null($this->storageSetupFunction)) {
            throw new \LogicException();
        }
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
     * @param AdapterInterface $storageAdapter
     * @return void
     */
    public function setStorageAdapter(AdapterInterface $storageAdapter)
    {
        $this->storageAdaper = $storageAdapter;
    }

    /**
     * @return AdapterInterface
     */
    public function getStorageAdapter()
    {
        return $this->storageAdaper;
    }

    /**
     * @param  callable $function
     * @return void
     */
    public function setStorageSetupFunction(callable $function)
    {
        $this->storageSetupFunction = $function;
    }

    /**
     * @return callable|\Closure
     */
    public function getStorageSetupFunction()
    {
        if ($storageAdapter = $this->storageAdaper) {
            return function () use ($storageAdapter) {
                return new Storage($storageAdapter);
            };
        }

        return $this->storageSetupFunction;
    }

}
