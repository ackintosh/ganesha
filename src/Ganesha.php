<?php
namespace Ackintosh;

use Ackintosh\Ganesha\Exception\StorageException;
use Ackintosh\Ganesha\StrategyInterface;

class Ganesha
{
    /**
     * @var string
     */
    const EVENT_TRIPPED = 'tripped';

    /**
     * @var string
     */
    const EVENT_CALMED_DOWN = 'calmed_down';

    /**
     * @var string
     */
    const EVENT_STORAGE_ERROR = 'storage_error';

    /**
     * the status between failure count 0 and trip.
     * @var int
     */
    const STATUS_CALMED_DOWN = 1;

    /**
     * the status between trip and calm down.
     * @var int
     */
    const STATUS_TRIPPED  = 2;

    /**
     * @var StrategyInterface
     */
    private $strategy;

    /**
     * @var callable[]
     */
    private $subscribers = [];

    /**
     * @var bool
     */
    private static $disabled = false;

    /**
     * Ganesha constructor.
     *
     * @param StrategyInterface $strategy
     */
    public function __construct($strategy)
    {
        $this->strategy = $strategy;
    }

    /**
     * records failure
     *
     * @param string $service
     * @return void
     */
    public function failure($service): void
    {
        try {
            if ($this->strategy->recordFailure($service) === self::STATUS_TRIPPED) {
                $this->notify(self::EVENT_TRIPPED, $service, '');
            }
        } catch (StorageException $e) {
            $this->notify(self::EVENT_STORAGE_ERROR, $service, 'failed to record failure : ' . $e->getMessage());
        }
    }

    /**
     * records success
     *
     * @param string $service
     * @return void
     */
    public function success($service): void
    {
        try {
            if ($this->strategy->recordSuccess($service) === self::STATUS_CALMED_DOWN) {
                $this->notify(self::EVENT_CALMED_DOWN, $service, '');
            }
        } catch (StorageException $e) {
            $this->notify(self::EVENT_STORAGE_ERROR, $service, 'failed to record success : ' . $e->getMessage());
        }
    }

    /**
     * @param string $service
     * @return bool
     */
    public function isAvailable($service): bool
    {
        if (self::$disabled) {
            return true;
        }

        try {
            return $this->strategy->isAvailable($service);
        } catch (StorageException $e) {
            $this->notify(self::EVENT_STORAGE_ERROR, $service, 'failed to isAvailable : ' . $e->getMessage());
            // fail-silent
            return true;
        }
    }

    /**
     * @param callable $callable
     * @psalm-param callable(self::EVENT_*, string, string): void $callable
     * @return void
     */
    public function subscribe(callable $callable): void
    {
        $this->subscribers[] = $callable;
    }

    /**
     * @param string $event
     * @param string $service
     * @param string $message
     * @return void
     */
    private function notify(string $event, string $service, string $message): void
    {
        foreach ($this->subscribers as $s) {
            call_user_func_array($s, [$event, $service, $message]);
        }
    }

    /**
     * disable
     *
     * @return void
     */
    public static function disable(): void
    {
        self::$disabled = true;
    }

    /**
     * enable
     *
     * @return void
     */
    public static function enable(): void
    {
        self::$disabled = false;
    }

    /**
     * resets all counts
     *
     * @return void
     */
    public function reset(): void
    {
        $this->strategy->reset();
    }
}
