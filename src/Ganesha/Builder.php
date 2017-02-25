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
        $params['strategyClass'] = '\Ackintosh\Ganesha\Strategy\Count';
        return self::perform($params);
    }

    /**
     * @param  array $params
     * @return Ganesha
     */
    public static function buildWithRelativeStrategy(array $params)
    {
        $params['strategyClass'] = '\Ackintosh\Ganesha\Strategy\Relative';
        return self::perform($params);
    }

    /**
     * @return Ganesha
     * @throws \Exception
     */
    private static function perform($params)
    {
        $configuration = new Configuration($params);

        try {
            $configuration->validate();
        } catch (\Exception $e) {
            throw $e;
        }

        $ganesha = new Ganesha(
            call_user_func(
                array($configuration['strategyClass'], 'create'),
                $configuration
            )
        );

        if ($behaviorOnStorageError = $configuration['behaviorOnStorageError']) {
            $ganesha->setBehaviorOnStorageError($behaviorOnStorageError);
        }

        if ($behaviorOnTrip = $configuration['behaviorOnTrip']) {
            $ganesha->setBehaviorOnTrip($behaviorOnTrip);
        }

        return $ganesha;
    }
}
