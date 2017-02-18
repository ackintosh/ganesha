<?php
namespace Ackintosh\Ganesha;

use Ackintosh\Ganesha;
use Ackintosh\Ganesha\Storage\AdapterInterface;
use Ackintosh\Ganesha\Strategy\Absolute;

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
    public static function create()
    {
        return new self(new Configuration());
    }

    public function withFailureThreshold($threshold)
    {
        $this->configuration->setFailureThreshold($threshold);
        return $this;
    }

    /**
     * @param AdapterInterface $adapter
     * @return $this Builder
     */
    public function withAdapter(AdapterInterface $adapter)
    {
        $this->configuration->setAdapter($adapter);
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

        $this->configuration->setAdapterSetupFunction($function);
        return $this;
    }

    /**
     * @param int $interval
     * @return Builder
     */
    public function withIntervalToHalfOpen($interval)
    {
        $this->configuration->setIntervalToHalfOpen($interval);
        return $this;
    }

    /**
     * @param  int $countTTL
     * @return Builder
     */
    public function withCountTTL($countTTL)
    {
        $this->configuration->setCountTTL($countTTL);
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

        $this->configuration->setBehaviorOnStorageError($behavior);
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

        $this->configuration->setBehaviorOnTrip($behavior);
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
            $this->configuration->getFailureThreshold(),
            new Absolute()
        );
        $ganesha->setupStorage(
            $this->configuration->getAdapterSetupFunction(),
            $this->configuration->getCountTTL()
        );
        $ganesha->setIntervalToHalfOpen($this->configuration->getIntervalToHalfOpen());
        if ($behaviorOnStorageError = $this->configuration->getBehaviorOnStorageError()) {
            $ganesha->setBehaviorOnStorageError($behaviorOnStorageError);
        }
        if ($behaviorOnTrip = $this->configuration->getBehaviorOnTrip()) {
            $ganesha->setBehaviorOnTrip($behaviorOnTrip);
        }

        return $ganesha;
    }
}
