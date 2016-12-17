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
     * @var int
     */
    private $failureThreshold = 10;

    /**
     * @throws \LogicException
     * @return void
     */
    public function validate()
    {
        if (!$this->storageAdaper instanceof AdapterInterface) {
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
}
