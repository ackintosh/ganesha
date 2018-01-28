<?php
namespace Ackintosh\Ganesha;

use Ackintosh\Ganesha\Exception\StorageException;
use Ackintosh\Ganesha\Storage\Adapter\TumblingTimeWindowInterface;
use Ackintosh\Ganesha\Storage\Adapter\SlidingTimeWindowInterface;
use Ackintosh\Ganesha\Storage\AdapterInterface;

class Storage
{
    /**
     * @var Storage\AdapterInterface
     */
    private $adapter;

    /**
     * @var callable
     */
    private $serviceNameDecorator;

    /**
     * @var string
     */
    const KEY_PREFIX = 'ganesha_';

    /**
     * @var string
     */
    const KEY_SUFFIX_SUCCESS = '_success';

    /**
     * @var string
     */
    const KEY_SUFFIX_FAILURE = '_failure';

    /**
     * @var string
     */
    const KEY_SUFFIX_REJECTION = '_rejection';

    /**
     * @var string
     */
    const KEY_SUFFIX_LAST_FAILURE_TIME = '_last_failure_time';

    /**
     * @var string
     */
    const KEY_SUFFIX_STATUS = '_status';

    /**
     * Storage constructor.
     *
     * @param AdapterInterface $adapter
     * @param callable         $serviceNameDecorator
     */
    public function __construct(AdapterInterface $adapter, $serviceNameDecorator)
    {
        $this->adapter = $adapter;
        $this->serviceNameDecorator = $serviceNameDecorator;
    }

    /**
     * returns count
     *
     * @param  string $key
     * @return int
     * @throws StorageException
     */
    private function getCount($key)
    {
        return $this->adapter->load($key);
    }

    /**
     * returns success count
     *
     * @param  string $key
     * @return int
     * @throws StorageException
     */
    public function getSuccessCountByCustomKey($key)
    {
        return $this->getCount($this->prefix($key) . self::KEY_SUFFIX_SUCCESS);
    }

    /**
     * returns failure count
     *
     * @param  string $key
     * @return int
     * @throws StorageException
     */
    public function getFailureCountByCustomKey($key)
    {
        return $this->getCount($this->prefix($key) . self::KEY_SUFFIX_FAILURE);
    }

    /**
     * returns rejection count
     *
     * @param  string $key
     * @return int
     * @throws StorageException
     */
    public function getRejectionCountByCustomKey($key)
    {
        return $this->getCount($this->prefix($key) . self::KEY_SUFFIX_REJECTION);
    }

    /**
     * returns failure count
     *
     * @param  string $service
     * @return int
     * @throws StorageException
     */
    public function getFailureCount($service)
    {
        return $this->getCount($this->failureKey($service));
    }

    /**
     * returns success count
     *
     * @param  string $service
     * @return int
     * @throws StorageException
     */
    public function getSuccessCount($service)
    {
        return $this->getCount($this->successKey($service));
    }

    /**
     * increments failure count
     *
     * @param  string $service
     * @return void
     * @throws StorageException
     */
    public function incrementFailureCount($service)
    {
        $this->adapter->increment($this->failureKey($service));
    }

    /**
     * decrements failure count
     *
     * @param  string $service
     * @return void
     * @throws StorageException
     */
    public function decrementFailureCount($service)
    {
        $this->adapter->decrement($this->failureKey($service));
    }

    /**
     * increments success count
     *
     * @param  string $service
     * @return void
     * @throws StorageException
     */
    public function incrementSuccessCount($service)
    {
        $this->adapter->increment($this->successKey($service));
    }

    /**
     * returns rejection count
     *
     * @param  string $service
     * @return int
     * @throws StorageException
     */
    public function getRejectionCount($service)
    {
        return $this->getCount($this->rejectionKey($service));
    }

    /**
     * increments rejection count
     *
     * @param  string $service
     * @return void
     * @throws StorageException
     */
    public function incrementRejectionCount($service)
    {
        $this->adapter->increment($this->rejectionKey($service));
    }

    /**
     * sets failure count
     *
     * @param $service
     * @param $failureCount
     * @throws StorageException
     */
    public function setFailureCount($service, $failureCount)
    {
        $this->adapter->save($this->failureKey($service), $failureCount);
    }

    /**
     * sets last failure time
     *
     * @param  string $service
     * @param  int    $lastFailureTime
     * @return void
     * @throws StorageException
     */
    public function setLastFailureTime($service, $lastFailureTime)
    {
        $this->adapter->saveLastFailureTime($this->lastFailureKey($service), $lastFailureTime);
    }

    /**
     * returns last failure time
     *
     * @param  string $service
     * @return int | null
     * @throws StorageException
     */
    public function getLastFailureTime($service)
    {
        return $this->adapter->loadLastFailureTime($this->lastFailureKey($service));
    }

    /**
     * sets status
     *
     * @param  string $service
     * @param  int    $status
     * @return void
     * @throws StorageException
     */
    public function setStatus($service, $status)
    {
        $this->adapter->saveStatus($this->statusKey($service), $status);
    }

    /**
     * returns status
     *
     * @param  string $service
     * @return int
     * @throws StorageException
     */
    public function getStatus($service)
    {
        return $this->adapter->loadStatus($this->statusKey($service));
    }

    /**
     * @return void
     */
    public function reset()
    {
        $this->adapter->reset();
    }

    /**
     * @return bool
     */
    public function supportFixedTimeWindow()
    {
        return $this->adapter instanceof TumblingTimeWindowInterface;
    }

    /**
     * @return bool
     */
    public function supportRollingTimeWindow()
    {
        return $this->adapter instanceof SlidingTimeWindowInterface;
    }

    /**
     * @param  string $service
     * @return string
     */
    private function key($service)
    {
        if ($this->serviceNameDecorator) {
            $service = call_user_func($this->serviceNameDecorator, $service);
        }

        return $this->prefix($service);
    }

    /**
     * @param  string $key
     * @return string
     */
    private function prefix($key)
    {
        return self::KEY_PREFIX . $key;
    }

    /**
     * @param  string $service
     * @return string
     */
    private function successKey($service)
    {
        return $this->key($service) . self::KEY_SUFFIX_SUCCESS;
    }

    /**
     * @param  string $service
     * @return string
     */
    private function failureKey($service)
    {
        return $this->key($service) . self::KEY_SUFFIX_FAILURE;
    }

    /**
     * @param  string $service
     * @return string
     */
    private function rejectionKey($service)
    {
        return $this->key($service) . self::KEY_SUFFIX_REJECTION;
    }

    /**
     * @param  string $service
     * @return string
     */
    private function lastFailureKey($service)
    {
        return $this->prefix($service) . self::KEY_SUFFIX_LAST_FAILURE_TIME;
    }

    /**
     * @param  string $service
     * @return string
     */
    private function statusKey($service)
    {
        return $this->prefix($service) . self::KEY_SUFFIX_STATUS;
    }
}
