<?php
namespace Ackintosh\Ganesha;

use Ackintosh\Ganesha\Storage\AdapterInterface;
use Ackintosh\Ganesha\Storage\StorageKeys;
use Ackintosh\Ganesha\Storage\StorageKeysInterface;

class Configuration implements \ArrayAccess
{
    // Configuration keys
    const ADAPTER = 'adapter';
    const TIME_WINDOW = 'timeWindow';
    const FAILURE_RATE_THRESHOLD = 'failureRateThreshold';
    const FAILURE_COUNT_THRESHOLD = 'failureCountThreshold';
    const MINIMUM_REQUESTS = 'minimumRequests';
    const INTERVAL_TO_HALF_OPEN = 'intervalToHalfOpen';
    const STORAGE_KEYS = 'storageKeys';

    /**
     * @var array
     */
    private $params;

    public function __construct($params)
    {
        if (!isset($params[self::STORAGE_KEYS])) {
            $params[self::STORAGE_KEYS] = new StorageKeys();
        }
        $this->params = $params;
    }

    public function offsetSet($offset, $value)
    {
        $this->params[$offset] = $value;
    }

    public function offsetExists($offset)
    {
        return isset($this->params[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->params[$offset]);
    }

    public function offsetGet($offset)
    {
        return isset($this->params[$offset]) ? $this->params[$offset] : null;
    }

    public function adapter(): AdapterInterface
    {
        return $this->params[self::ADAPTER];
    }

    public function timeWindow(): int
    {
        return $this->params[self::TIME_WINDOW];
    }

    public function failureRateThreshold(): int
    {
        return $this->params[self::FAILURE_RATE_THRESHOLD];
    }

    public function failureCountThreshold(): int
    {
        return $this->params[self::FAILURE_COUNT_THRESHOLD];
    }

    public function minimumRequests(): int
    {
        return $this->params[self::MINIMUM_REQUESTS];
    }

    public function intervalToHalfOpen(): int
    {
        return $this->params[self::INTERVAL_TO_HALF_OPEN];
    }

    public function storageKeys(): StorageKeysInterface
    {
        return $this->params[self::STORAGE_KEYS];
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function validate(): void
    {
        if (isset($this->params[self::ADAPTER]) && !$this->params[self::ADAPTER] instanceof AdapterInterface) {
            throw new \InvalidArgumentException(get_class($this->params[self::ADAPTER]) . ' should be an instance of AdapterInterface');
        }

        if (isset($this->params[self::STORAGE_KEYS]) && !$this->params[self::STORAGE_KEYS] instanceof StorageKeysInterface) {
            throw new \InvalidArgumentException(get_class($this->params[self::STORAGE_KEYS]) . ' should be an instance of StorageKeysInterface');
        }

        foreach ([
                self::TIME_WINDOW,
                self::FAILURE_RATE_THRESHOLD,
                self::FAILURE_COUNT_THRESHOLD,
                self::MINIMUM_REQUESTS,
                self::INTERVAL_TO_HALF_OPEN
            ] as $name) {
            if (isset($this->params[$name])) {
                $v = $this->params[$name];
                if (!is_int($v) || $v < 1) {
                    throw new \InvalidArgumentException($name . ' should be an positive integer');
                }
            }
        }

        if (isset($this->params[self::FAILURE_RATE_THRESHOLD]) && $this->params[self::FAILURE_RATE_THRESHOLD] > 100) {
            throw new \InvalidArgumentException(self::FAILURE_RATE_THRESHOLD . ' should be equal or less than 100');
        }
    }
}
