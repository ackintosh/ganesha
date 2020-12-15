<?php
namespace Ackintosh\Ganesha\Storage\Adapter;

use Ackintosh\Ganesha;
use Ackintosh\Ganesha\Configuration;
use Ackintosh\Ganesha\Exception\StorageException;
use Ackintosh\Ganesha\Storage;
use Ackintosh\Ganesha\Storage\AdapterInterface;
use RuntimeException;

class Memcached implements AdapterInterface, TumblingTimeWindowInterface
{
    /**
     * @var \Memcached
     */
    private $memcached;

    /**
     * @var Ganesha\Context
     */
    private $context;

    /**
     * Memcached constructor.
     * @param \Memcached $memcached
     */
    public function __construct(\Memcached $memcached)
    {
        // initial_value in (increment|decrement) requires \Memcached::OPT_BINARY_PROTOCOL
        $memcached->setOption(\Memcached::OPT_BINARY_PROTOCOL, true);
        $this->memcached = $memcached;
    }

    /**
     * @return bool
     */
    public function supportCountStrategy(): bool
    {
        return true;
    }

    /**
     * @return bool
     */
    public function supportRateStrategy(): bool
    {
        return true;
    }

    /**
     * @param Ganesha\Context $context
     * @return void
     * @codeCoverageIgnore
     */
    public function setContext(Ganesha\Context $context): void
    {
        $this->context = $context;
    }

    /**
     * @inheritdoc
     */
    public function setConfiguration(Configuration $configuration): void
    {
        // nop
    }

    /**
     * @param string $service
     * @return int
     * @throws StorageException
     */
    public function load(string $service): int
    {
        $r = (int)$this->memcached->get($service);
        $this->throwExceptionIfErrorOccurred();
        return $r;
    }

    /**
     * @param string $service
     * @param int $count
     * @return void
     * @throws StorageException
     */
    public function save(string $service, int $count): void
    {
        if (!$this->memcached->set($service, $count)) {
            throw new StorageException('failed to set the value : ' . $this->memcached->getResultMessage());
        }
    }

    /**
     * @param string $service
     * @return void
     * @throws StorageException
     */
    public function increment(string $service): void
    {
        $expiry = 0;
        if ($this->context->strategy() === Ganesha\Context::STRATEGY_RATE_TUMBLING_TIME_WINDOW) {
            // Set the expiry time to make ensure outdated items of TumblingTimeWindow will be removed.
            $expiry = $this->context->configuration()->timeWindow() * 10;
        }

        // requires \Memcached::OPT_BINARY_PROTOCOL
        if ($this->memcached->increment($service, 1, 1, $expiry) === false) {
            throw new StorageException('failed to increment failure count : ' . $this->memcached->getResultMessage());
        }
    }

    /**
     * @param string $service
     * @return void
     * @throws StorageException
     */
    public function decrement(string $service): void
    {
        // requires \Memcached::OPT_BINARY_PROTOCOL
        if ($this->memcached->decrement($service, 1, 0) === false) {
            throw new StorageException('failed to decrement failure count : ' . $this->memcached->getResultMessage());
        }
    }

    /**
     * @param string $service
     * @param int    $lastFailureTime
     * @throws StorageException
     */
    public function saveLastFailureTime(string $service, int $lastFailureTime): void
    {
        if (!$this->memcached->set($service, $lastFailureTime)) {
            throw new StorageException('failed to set the last failure time : ' . $this->memcached->getResultMessage());
        }
    }

    /**
     * @param  string $service
     * @return int
     * @throws StorageException
     */
    public function loadLastFailureTime(string $service): int
    {
        $r = $this->memcached->get($service);
        $this->throwExceptionIfErrorOccurred();
        return $r;
    }

    /**
     * @param string $service
     * @param int    $status
     * @throws StorageException
     */
    public function saveStatus(string $service, int $status): void
    {
        if (!$this->memcached->set($service, $status)) {
            throw new StorageException('failed to set the status : ' . $this->memcached->getResultMessage());
        }
    }

    /**
     * @param  string $service
     * @return int
     * @throws StorageException
     */
    public function loadStatus(string $service): int
    {
        $status = $this->memcached->get($service);
        $this->throwExceptionIfErrorOccurred();
        if ($status === false && $this->memcached->getResultCode() === \Memcached::RES_NOTFOUND) {
            $this->saveStatus($service, Ganesha::STATUS_CALMED_DOWN);
            return Ganesha::STATUS_CALMED_DOWN;
        }

        return $status;
    }

    public function reset(): void
    {
        if (!$this->memcached->getStats()) {
            throw new RuntimeException("Couldn't connect to memcached.");
        }

        // getAllKeys() with OPT_BINARY_PROTOCOL is not suppoted.
        // So temporarily disable it.
        $this->memcached->setOption(\Memcached::OPT_BINARY_PROTOCOL, false);
        $keys = $this->memcached->getAllKeys();
        $this->memcached->setOption(\Memcached::OPT_BINARY_PROTOCOL, true);
        if (!$keys) {
            $resultCode = $this->memcached->getResultCode();
            if ($resultCode === 0) {
                // no keys
                return;
            }
            $message = sprintf(
                'failed to get memcached keys. resultCode: %d, resultMessage: %s',
                $resultCode,
                $this->memcached->getResultMessage()
            );
            throw new RuntimeException($message);
        }

        foreach ($keys as $k) {
            if ($this->isGaneshaData($k)) {
                $this->memcached->delete($k);
            }
        }
    }

    public function isGaneshaData(string $key): bool
    {
        $regex = sprintf(
            '#\A%s.+(%s|%s|%s|%s|%s)\z#',
            Storage\StorageKeys::KEY_PREFIX,
            Storage\StorageKeys::KEY_SUFFIX_SUCCESS,
            Storage\StorageKeys::KEY_SUFFIX_FAILURE,
            Storage\StorageKeys::KEY_SUFFIX_REJECTION,
            Storage\StorageKeys::KEY_SUFFIX_LAST_FAILURE_TIME,
            Storage\StorageKeys::KEY_SUFFIX_STATUS
        );

        return preg_match($regex, $key) === 1;
    }

    /**
     * Throws an exception if some error occurs in memcached.
     *
     * @return void
     * @throws StorageException
     */
    private function throwExceptionIfErrorOccurred(): void
    {
        $errorResultCodes = [
            \Memcached::RES_FAILURE,
            \Memcached::RES_SERVER_TEMPORARILY_DISABLED,
            \Memcached::RES_SERVER_MEMORY_ALLOCATION_FAILURE,
        ];

        if (in_array($this->memcached->getResultCode(), $errorResultCodes, true)) {
            throw new StorageException($this->memcached->getResultMessage());
        }
    }
}
