<?php
namespace Ackintosh\Ganesha;

use Ackintosh\Ganesha;
use Ackintosh\Ganesha\Storage\AdapterInterface;

class Builder
{
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * Builder constructor.
     *
     * @param Configuration $configuration
     */
    private function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @return Builder
     */
    public static function create($params)
    {
        $params['strategyClass'] = '\Ackintosh\Ganesha\Strategy\Absolute';
        return new self(new Configuration($params));
    }

    public static function createWithRelativeStrategy($params)
    {
        $params['strategyClass'] = '\Ackintosh\Ganesha\Strategy\Relative';
        return new self(new Configuration($params));
    }

    public function withFailureThreshold($threshold)
    {
        $this->configuration['failureThreshold'] = $threshold;
        return $this;
    }

    /**
     * @param AdapterInterface $adapter
     * @return $this Builder
     */
    public function withAdapter(AdapterInterface $adapter)
    {
        $this->configuration['adapter'] = $adapter;
        return $this;
    }

    /**
     * @param  callable $function
     * @return Builder  $this
     */
    public function withAdapterSetupFunction($function)
    {
        if (!is_callable($function)) {
            throw new \InvalidArgumentException();
        }

        $this->configuration['adapterSetupFunction'] = $function;
        return $this;
    }

    /**
     * @param int $interval
     * @return Builder
     */
    public function withIntervalToHalfOpen($interval)
    {
        $this->configuration['intervalToHalfOpen'] = $interval;
        return $this;
    }

    /**
     * @param  int $ttl
     * @return Builder
     */
    public function withCountTTL($ttl)
    {
        $this->configuration['countTTL'] = $ttl;
        return $this;
    }

    /**
     * @param  callable $behavior
     * @return Builder
     */
    public function withBehaviorOnStorageError($behavior)
    {
        if (!is_callable($behavior)) {
            throw new \InvalidArgumentException();
        }

        $this->configuration['behaviorOnStorageError'] = $behavior;
        return $this;
    }

    /**
     * @param  callable $behavior
     * @return Builder
     */
    public function withBehaviorOnTrip($behavior)
    {
        if (!is_callable($behavior)) {
            throw new \InvalidArgumentException();
        }

        $this->configuration['behaviorOnTrip'] = $behavior;
        return $this;
    }

    /**
     * @return Ganesha
     * @throws \Exception
     */
    public function build()
    {
        try {
            $this->configuration->validate();
        } catch (\Exception $e) {
            throw $e;
        }

        $ganesha = new Ganesha(
            call_user_func(
                array($this->configuration['strategyClass'], 'create'),
                $this->configuration
            )
        );
        if ($behaviorOnStorageError = $this->configuration['behaviorOnStorageError']) {
            $ganesha->setBehaviorOnStorageError($behaviorOnStorageError);
        }
        if ($behaviorOnTrip = $this->configuration['behaviorOnTrip']) {
            $ganesha->setBehaviorOnTrip($behaviorOnTrip);
        }

        return $ganesha;
    }
}
