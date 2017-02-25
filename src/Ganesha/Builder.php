<?php
namespace Ackintosh\Ganesha;

use Ackintosh\Ganesha;

class Builder
{
    /**
     * @return Ganesha
     */
    public static function build($params)
    {
        $params['strategyClass'] = '\Ackintosh\Ganesha\Strategy\Absolute';
        return self::perform($params);
    }

    /**
     * @return Ganesha
     */
    public static function buildWithRelativeStrategy($params)
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
