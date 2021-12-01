<?php
namespace Ackintosh\Ganesha\Storage\Adapter;

use Ackintosh\Ganesha;
use Ackintosh\Ganesha\Configuration;
use Ackintosh\Ganesha\Exception\StorageException;
use Ackintosh\Ganesha\Storage\AdapterInterface;
use Ackintosh\Ganesha\Storage\StorageKeys;

class Apcu implements AdapterInterface, TumblingTimeWindowInterface
{
    /** @var ApcuStore */
    private $apcuStore;

    /** @var StorageKeys */
    private $storageKeys;

    /**
     * @param ApcuStore|null $apcuStore Backing store for testing (optional)
     */
    public function __construct(ApcuStore $apcuStore = null)
    {
        $this->apcuStore = $apcuStore ?? new ApcuStore();
    }

    /**
     * Returns returns whether the adapter supports counting strategy
     * @return bool
     */
    public function supportCountStrategy(): bool
    {
        return true;
    }

    /**
     * Returns returns whether the adapter supports rating strategy
     * @return bool
     */
    public function supportRateStrategy(): bool
    {
        return true;
    }

    /**
     * @param Ganesha\Context $context
     * @return void
     */
    public function setContext(Ganesha\Context $context): void
    {
        $this->storageKeys = $context->configuration()->storageKeys();
    }

    /**
     * @inheritdoc
     */
    public function setConfiguration(Configuration $configuration): void
    {
        // nop
    }

    /**
     * @param  string $key
     * @return int
     */
    public function load(string $key): int
    {
        return (int) $this->apcuStore->fetch($key);
    }

    /**
     * @param  string $key
     * @param  int    $count
     * @return void
     */
    public function save(string $key, int $count): void
    {
        $result = $this->apcuStore->store($key, $count);

        if (!$result) {
            throw new StorageException('Failed to set the value.');
        }
    }

    /**
     * @param  string $key
     * @return void
     */
    public function increment(string $key): void
    {
        $this->apcuStore->inc($key, 1, $success);

        if (!$success) {
            throw new StorageException('Failed to increment failure count.');
        }
    }

    /**
     * decrement failure count
     *
     * If the operation would decrease the value below 0, the new value must be 0.
     *
     * @param  string $key
     * @return void
     */
    public function decrement(string $key): void
    {
        $success = true;
        if ($this->load($key) > 0) {
            $result = $this->apcuStore->dec($key, 1, $success);

            // Handle a possible race condition: if the value changes between
            // load() and dec() above, we may end up with a negative number.
            // Using inc() to correct since the remote possibility of another
            // race condition leaving us with a small positive number is better
            // than leaving this value outside the valid domain >= 0.
            if ($success && $result < 0) {
                $this->apcuStore->inc($key, 1, $success);
            }
        }

        if (!$success) {
            throw new StorageException('Failed to decrement failure count.');
        }
    }

    /**
     * sets last failure time
     *
     * @param  string $key
     * @param  int    $lastFailureTime
     * @return void
     */
    public function saveLastFailureTime(string $key, int $lastFailureTime): void
    {
        $success = $this->apcuStore->store($key, $lastFailureTime);

        if (!$success) {
            throw new StorageException('Failed to set the last failure time.');
        }
    }

    /**
     * returns last failure time
     *
     * @param  string $key
     * @return int | null
     */
    public function loadLastFailureTime(string $key): ?int
    {
        return $this->apcuStore->fetch($key) ?: null;
    }

    /**
     * sets status
     *
     * @param  string $key
     * @param  int    $status
     * @return void
     */
    public function saveStatus(string $key, int $status): void
    {
        $success = $this->apcuStore->store($key, $status);

        if (!$success) {
            throw new StorageException('Failed to set the status.');
        }
    }

    /**
     * returns status
     *
     * @param  string $key
     * @return int
     */
    public function loadStatus(string $key): int
    {
        $status = $this->apcuStore->fetch($key, $success);
        if (!$success) {
            if ($this->apcuStore->exists($key)) {
                throw new StorageException('Failed to load the status.');
            } else {
                $this->saveStatus($key, Ganesha::STATUS_CALMED_DOWN);
                return Ganesha::STATUS_CALMED_DOWN;
            }
        }

        return $status;
    }

    /**
     * resets all counts
     *
     * @return void
     */
    public function reset(): void
    {
        $keyPrefix = preg_quote($this->storageKeys->prefix(), '/');
        $keySuffixes = array_map(
            function (string $s) {
                return preg_quote($s, '/');
            },
            [
                $this->storageKeys->success(),
                $this->storageKeys->failure(),
                $this->storageKeys->rejection(),
                $this->storageKeys->lastFailureTime(),
                $this->storageKeys->status(),
            ]
        );

        $keyRegex = sprintf(
            '/^%s.+(%s)$/',
            $keyPrefix,
            implode('|', $keySuffixes)
        );

        $this->apcuStore->delete($this->apcuStore->getIterator($keyRegex));
    }
}
