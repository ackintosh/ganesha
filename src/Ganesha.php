<?php
namespace Ackintosh;

use Ackintosh\Ganesha\Exception\StorageException;

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
     * @var \Ackintosh\Ganesha\StrategyInterface
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
     * @param \Ackintosh\Ganesha\StrategyInterface $strategy
     */
    public function __construct($strategy)
    {
        $this->strategy = $strategy;
    }

    /**
     * records failure
     *
     * @return void
     */
    public function failure($serviceName)
    {
        try {
            if ($this->strategy->recordFailure($serviceName) === self::STATUS_TRIPPED) {
                $this->notify(self::EVENT_TRIPPED, $serviceName, '');
            }
        } catch (StorageException $e) {
            $this->notify(self::EVENT_STORAGE_ERROR, $serviceName, 'failed to record failure : ' . $e->getMessage());
        }
    }

    /**
     * records success
     *
     * @return void
     */
    public function success($serviceName)
    {
        try {
            if ($this->strategy->recordSuccess($serviceName) === self::STATUS_CALMED_DOWN) {
                $this->notify(self::EVENT_CALMED_DOWN, $serviceName, '');
            }
        } catch (StorageException $e) {
            $this->notify(self::EVENT_STORAGE_ERROR, $serviceName, 'failed to record success : ' . $e->getMessage());
        }
    }

    /**
     * @return bool
     */
    public function isAvailable($serviceName)
    {
        if (self::$disabled) {
            return true;
        }

        try {
            return $this->strategy->isAvailable($serviceName);
        } catch (StorageException $e) {
            $this->notify(self::EVENT_STORAGE_ERROR, $serviceName, 'failed to isAvailable : ' . $e->getMessage());
            // fail-silent
            return true;
        }
    }

    /**
     * @param callable $callable
     * @return void
     */
    public function subscribe(callable $callable)
    {
        $this->subscribers[] = $callable;
    }

    /**
     * @param string $event
     * @param string $serviceName
     * @param string $message
     * @return void
     */
    private function notify($event, $serviceName, $message)
    {
        foreach ($this->subscribers as $s) {
            call_user_func_array($s, [$event, $serviceName, $message]);
        }
    }

    /**
     * disable
     *
     * @return void
     */
    public static function disable()
    {
        self::$disabled = true;
    }

    /**
     * enable
     *
     * @return void
     */
    public static function enable()
    {
        self::$disabled = false;
    }

    /**
     * resets all counts
     *
     * @return void
     */
    public function reset()
    {
        $this->strategy->reset();
    }
}
