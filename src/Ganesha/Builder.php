<?php
namespace Ackintosh\Ganesha;

use Ackintosh\Ganesha\Strategy;

class Builder
{
    /**
     * @return Strategy\Rate\Builder
     */
    public static function withRateStrategy(): Strategy\Rate\Builder
    {
        return new Strategy\Rate\Builder();
    }

    /**
     * @return Strategy\Count\Builder
     */
    public static function withCountStrategy(): Strategy\Count\Builder
    {
        return new Strategy\Count\Builder();
    }
}
