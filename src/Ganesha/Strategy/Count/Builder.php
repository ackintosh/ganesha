<?php
namespace Ackintosh\Ganesha\Strategy\Count;

use Ackintosh\Ganesha;
use Ackintosh\Ganesha\Configuration;
use Ackintosh\Ganesha\Storage\AdapterInterface;

class Builder
{
    use Ganesha\Traits\BuildGanesha;

    /** @var array */
    private $params;

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
     * @param AdapterInterface $adapter
     * @return $this
     */
    public function adapter(AdapterInterface $adapter): self
    {
        $this->params[Configuration::ADAPTER] = $adapter;
        return $this;
    }

    /**
     * @param int $failureCountThreshold
     * @return $this
     */
    public function failureCountThreshold(int $failureCountThreshold): self
    {
        $this->params[Configuration::FAILURE_COUNT_THRESHOLD] = $failureCountThreshold;
        return $this;
    }

    /**
     * @param int $intervalToHalfOpen
     * @return $this
     */
    public function intervalToHalfOpen(int $intervalToHalfOpen): self
    {
        $this->params[Configuration::INTERVAL_TO_HALF_OPEN] = $intervalToHalfOpen;
        return $this;
    }

    /**
     * @param Ganesha\Storage\StorageKeysInterface $storageKeys
     * @return $this
     */
    public function storageKeys(Ganesha\Storage\StorageKeysInterface $storageKeys): self
    {
        $this->params[Configuration::STORAGE_KEYS] = $storageKeys;
        return $this;
    }
}
