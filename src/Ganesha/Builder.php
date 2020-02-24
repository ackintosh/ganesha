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
        return self::perform('\Ackintosh\Ganesha\Strategy\Rate', $params);
    }

    /**
     * @param  array $params
     * @return Ganesha
     */
    public static function buildWithCountStrategy(array $params): Ganesha
    {
        return self::perform('\Ackintosh\Ganesha\Strategy\Count', $params);
    }

    /**
     * @param string $strategyClass
     * @param array $params
     * @return Ganesha
     * @throws InvalidArgumentException
     */
    private static function perform(string $strategyClass, array $params): Ganesha
    {
        call_user_func([$strategyClass, 'validate'], $params);

        $configuration = new Configuration($params);
        $configuration->validate();
        $ganesha = new Ganesha(
            call_user_func(
                [$strategyClass, 'create'],
                $configuration
            )
        );

        return $ganesha;
    }
}
