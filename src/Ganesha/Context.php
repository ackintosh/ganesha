<?php
namespace Ackintosh\Ganesha;

use Ackintosh\Ganesha\Storage\Adapter\SlidingTimeWindowInterface;
use Ackintosh\Ganesha\Storage\Adapter\TumblingTimeWindowInterface;
use Ackintosh\Ganesha\Storage\AdapterInterface;
use Ackintosh\Ganesha\Strategy\Count;
use Ackintosh\Ganesha\Strategy\Rate;

class Context
{
    /**
     * @var string
     */
    const STRATEGY_COUNT = 'count';

    /**
     * @var string
     */
    const STRATEGY_RATE_TUMBLING_TIME_WINDOW = 'rate_tumbling_time_window';

    /**
     * @var string
     */
    const STRATEGY_RATE_SLIDING_TIME_WINDOW = 'rate_sliding_time_window';

    /**
     * @var string
     */
    private $strategy;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @param string $strategyClass
     * @param AdapterInterface $adapter
     * @param Configuration $configuration
     */
    public function __construct(string $strategyClass, AdapterInterface $adapter, Configuration $configuration)
    {
        $this->strategy = $this->determineStrategyContext($strategyClass, $adapter);
        $this->configuration = $configuration;
    }

    public function strategy(): string
    {
        return $this->strategy;
    }

    public function configuration(): Configuration
    {
        return $this->configuration;
    }

    private function determineStrategyContext(string $strategyClass, AdapterInterface $adapter): string
    {
        switch ($strategyClass) {
            case Count::class:
                return self::STRATEGY_COUNT;
                break;
            case Rate::class:
                if ($adapter instanceof SlidingTimeWindowInterface) {
                    return self::STRATEGY_RATE_SLIDING_TIME_WINDOW;
                } elseif ($adapter instanceof TumblingTimeWindowInterface) {
                    return self::STRATEGY_RATE_TUMBLING_TIME_WINDOW;
                } else {
                    throw new \InvalidArgumentException('Adapter should implement SlidingTimeWindowInterface or TumblingTimeWindowInterface');
                }
                break;
            default:
                throw new \InvalidArgumentException('Unknown strategy class name: ' . $strategyClass);
                break;
        }
    }
}
