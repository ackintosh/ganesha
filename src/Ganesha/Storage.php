<?php
namespace Ackintosh\Ganesha;

use Ackintosh\Ganesha\Storage\AdapterInterface;

class Storage
{
    /**
     * @var Storage\AdapterInterface
     */
    private $adapter;

    /**
     * @var int
     */
    private $countTTL;

    /**
     * Storage constructor.
     *
     * @param AdapterInterface $adapter
     * @param int              $countTTL
     */
    public function __construct(AdapterInterface $adapter, $countTTL)
    {
        $this->adapter = $adapter;
        $this->countTTL = $countTTL;
    }

    /**
     * returns failure count
     *
     * @param  string $serviceName
     * @return int
     */
    public function getFailureCount($serviceName)
    {
        return $this->adapter->load($serviceName);
    }

    /**
     * increments failure count
     *
     * @param  string $serviceName
     * @return void
     */
    public function incrementFailureCount($serviceName)
    {
        $this->adapter->increment($serviceName, $this->countTTL);
    }

    /**
     * decrements failure count
     *
     * @param  string $serviceName
     * @return void
     */
    public function decrementFailureCount($serviceName)
    {
        $this->adapter->decrement($serviceName, $this->countTTL);
    }

    /**
     * sets failure count
     *
     * @param $serviceName
     * @param $failureCount
     */
    public function setFailureCount($serviceName, $failureCount)
    {
        $this->adapter->save($serviceName, $failureCount, $this->countTTL);
    }

    /**
     * sets last failure time
     *
     * @param  string $serviceName
     * @param  float  $lastFailureTime
     * @return void
     */
    public function setLastFailureTime($serviceName, $lastFailureTime)
    {
        $this->adapter->saveLastFailureTime($serviceName, $lastFailureTime);
    }

    /**
     * returns last failure time
     *
     * @param  string $serviceName
     * @return float | null
     */
    public function getLastFailureTime($serviceName)
    {
        return $this->adapter->loadLastFailureTime($serviceName);
    }

    /**
     * sets status
     *
     * @param  string $serviceName
     * @param  int    $status
     * @return void
     */
    public function setStatus($serviceName, $status)
    {
        $this->adapter->saveStatus($serviceName, $status);
    }

    /**
     * returns status
     *
     * @param  string $serviceName
     * @return int
     */
    public function getStatus($serviceName)
    {
        return $this->adapter->loadStatus($serviceName);
    }
}
