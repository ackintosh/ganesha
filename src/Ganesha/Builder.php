<?php

namespace Ackintosh\Ganesha;

/**
 * A front end of the strategy specific builders
 *
 * @package Ackintosh\Ganesha
 */
class Builder
{
    public static function withRateStrategy(): Strategy\Rate\Builder
    {
        return new Strategy\Rate\Builder();
    }

    public static function withCountStrategy(): Strategy\Count\Builder
    {
        return new Strategy\Count\Builder();
    }
}
