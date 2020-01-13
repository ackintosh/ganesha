<?php
namespace Ackintosh\Ganesha;

use Ackintosh\Ganesha;
use InvalidArgumentException;

class Builder
{
    /**
     * @param  array $params
     * @return Ganesha
     */
    public static function build(array $params): Ganesha
    {
        $params['strategyClass'] = '\Ackintosh\Ganesha\Strategy\Rate';
        return self::perform($params);
    }

    /**
     * @param  array $params
     * @return Ganesha
     */
    public static function buildWithCountStrategy(array $params): Ganesha
    {
        $params['strategyClass'] = '\Ackintosh\Ganesha\Strategy\Count';
        return self::perform($params);
    }

    /**
     * @param array $params
     * @return Ganesha
     * @throws InvalidArgumentException
     */
    private static function perform(array $params): Ganesha
    {
        call_user_func([$params['strategyClass'], 'validate'], $params);

        $configuration = new Configuration($params);
        $ganesha = new Ganesha(
            call_user_func(
                [$configuration['strategyClass'], 'create'],
                $configuration
            )
        );

        return $ganesha;
    }
}
