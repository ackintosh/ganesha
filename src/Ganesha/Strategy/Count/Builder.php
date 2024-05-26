<?php

namespace Ackintosh\Ganesha\Strategy\Count;

use Ackintosh\Ganesha;
use Ackintosh\Ganesha\Configuration;
use Ackintosh\Ganesha\Storage\AdapterInterface;
use Ackintosh\Ganesha\Storage\Adapter\SlidingTimeWindowInterface;
use Ackintosh\Ganesha\Storage\Adapter\TumblingTimeWindowInterface;

class Builder
{
    use Ganesha\Traits\BuildGanesha;

    private array $params = [];

    /** @var array */
    private static $requirements = [
        Configuration::ADAPTER,
        Configuration::FAILURE_COUNT_THRESHOLD,
        Configuration::INTERVAL_TO_HALF_OPEN,
    ];

    /** @var string */
    private static $strategyClass = 'Ackintosh\Ganesha\Strategy\Count';

    /** @var string */
    private static $adapterRequirement = 'supportCountStrategy';

    /**
     * @psalm-param (AdapterInterface&SlidingTimeWindowInterface)|(AdapterInterface&TumblingTimeWindowInterface) $adapter
     */
    public function adapter(AdapterInterface $adapter): self
    {
        $this->params[Configuration::ADAPTER] = $adapter;
        return $this;
    }

    /**
     * @psalm-param int<1, max> $failureCountThreshold
     */
    public function failureCountThreshold(int $failureCountThreshold): self
    {
        $this->params[Configuration::FAILURE_COUNT_THRESHOLD] = $failureCountThreshold;
        return $this;
    }

    /**
     * @psalm-param int<1, max> $intervalToHalfOpen
     */
    public function intervalToHalfOpen(int $intervalToHalfOpen): self
    {
        $this->params[Configuration::INTERVAL_TO_HALF_OPEN] = $intervalToHalfOpen;
        return $this;
    }

    public function storageKeys(Ganesha\Storage\StorageKeysInterface $storageKeys): self
    {
        $this->params[Configuration::STORAGE_KEYS] = $storageKeys;
        return $this;
    }
}
