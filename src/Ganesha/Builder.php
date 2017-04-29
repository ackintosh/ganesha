<?php
namespace Ackintosh\Ganesha;

use Ackintosh\Ganesha;

class Builder
{
    /**
     * @param  array $params
     * @return Ganesha
     */
    public static function build(array $params)
    {
        $params['strategyClass'] = '\Ackintosh\Ganesha\Strategy\Rate';
        return self::perform($params);
    }

    /**
     * @param  array $params
     * @return Ganesha
     */
    public static function buildWithCountStrategy(array $params)
    {
        $params['strategyClass'] = '\Ackintosh\Ganesha\Strategy\Count';
        return self::perform($params);
    }

    /**
     * @return Ganesha
     * @throws \Exception
     */
    private static function perform($params)
    {
        call_user_func(array($params['strategyClass'], 'validate'), $params);

        $configuration = new Configuration($params);
        $ganesha = new Ganesha(
            call_user_func(
                array($configuration['strategyClass'], 'create'),
                $configuration
            )
        );

        $ganesha->setBehaviorOnStorageError($configuration['onStorageError']);
        $ganesha->setBehaviorOnTrip($configuration['onTrip']);
        $ganesha->setBehaviorOnCalmedDown($configuration['onCalmedDown']);

        return $ganesha;
    }
}
