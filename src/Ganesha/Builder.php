<?php
namespace Ackintosh\Ganesha;

use Ackintosh\Ganesha\Strategy;

/**
 * A front end of the strategy specific builders
 *
 * @package Ackintosh\Ganesha
 */
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
