<?php
namespace Ackintosh\Ganesha\Strategy;

use Ackintosh\Ganesha;
use Ackintosh\Ganesha\Configuration;
use Ackintosh\Ganesha\Exception\StorageException;
use Ackintosh\Ganesha\Storage;
use Ackintosh\Ganesha\StrategyInterface;

class Count implements StrategyInterface
{
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var \Ackintosh\Ganesha\Storage
     */
    private $storage;

    /**
     * @var array
     */
    private static $requirements = [
        'adapter',
        'failureCountThreshold',
        'intervalToHalfOpen',
    ];

    /**
     * @param Configuration $configuration
     */
    private function __construct(Configuration $configuration, Storage $storage)
    {
        $this->configuration = $configuration;
        $this->storage = $storage;
    }

    /**
     * @param array $params
     * @throws \LogicException
     */
    public static function validate($params)
    {
        foreach (self::$requirements as $r) {
            if (!isset($params[$r])) {
                throw new \LogicException($r . ' is required');
            }
        }

        if (!call_user_func([$params['adapter'], 'supportCountStrategy'])) {
            throw new \InvalidArgumentException("{$params['adapter']} doesn't support Count Strategy.");
        }
    }

    /**
     * @param Configuration $configuration
     * @return Count
     */
    public static function create(Configuration $configuration)
    {
        $strategy = new self(
            $configuration,
            new Storage(
                $configuration['adapter'],
                null
            )
        );

        return $strategy;
    }

    /**
     * @return int
     */
    public function recordFailure($service)
    {
        $this->storage->setLastFailureTime($service, time());
        $this->storage->incrementFailureCount($service);

        if ($this->storage->getFailureCount($service) >= $this->configuration['failureCountThreshold']
            && $this->storage->getStatus($service) === Ganesha::STATUS_CALMED_DOWN
        ) {
            $this->storage->setStatus($service, Ganesha::STATUS_TRIPPED);
            return Ganesha::STATUS_TRIPPED;
        }

        return Ganesha::STATUS_CALMED_DOWN;
    }

    /**
     * @return void
     */
    public function recordSuccess($service)
    {
        $this->storage->decrementFailureCount($service);

        if ($this->storage->getFailureCount($service) === 0
            && $this->storage->getStatus($service) === Ganesha::STATUS_TRIPPED
        ) {
            $this->storage->setStatus($service, Ganesha::STATUS_CALMED_DOWN);
        }
    }

    /**
     * @return void
     */
    public function reset()
    {
        $this->storage->reset();
    }

    /**
     * @return bool
     */
    public function isAvailable($service)
    {
        try {
            return $this->isClosed($service) || $this->isHalfOpen($service);
        } catch (StorageException $e) {
            throw $e;
        }
    }

    /**
     * @return bool
     * @throws StorageException
     */
    private function isClosed($service)
    {
        try {
            return $this->storage->getFailureCount($service) < $this->configuration['failureCountThreshold'];
        } catch (StorageException $e) {
            throw $e;
        }
    }

    /**
     * @return bool
     * @throws StorageException
     */
    private function isHalfOpen($service)
    {
        if (is_null($lastFailureTime = $this->storage->getLastFailureTime($service))) {
            return false;
        }

        if ((time() - $lastFailureTime) > $this->configuration['intervalToHalfOpen']) {
            $this->storage->setFailureCount($service, $this->configuration['failureCountThreshold']);
            $this->storage->setLastFailureTime($service, time());
            return true;
        }

        return false;
    }
}
