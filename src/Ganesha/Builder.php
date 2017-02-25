<?php
namespace Ackintosh\Ganesha;

use Ackintosh\Ganesha;

class Builder
{
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * Builder constructor.
     *
     * @param Configuration $configuration
     */
    private function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @return Builder
     */
    public static function create($params)
    {
        $params['strategyClass'] = '\Ackintosh\Ganesha\Strategy\Absolute';
        return new self(new Configuration($params));
    }

    public static function createWithRelativeStrategy($params)
    {
        $params['strategyClass'] = '\Ackintosh\Ganesha\Strategy\Relative';
        return new self(new Configuration($params));
    }

    /**
     * @return Ganesha
     * @throws \Exception
     */
    public function build()
    {
        try {
            $this->configuration->validate();
        } catch (\Exception $e) {
            throw $e;
        }

        $ganesha = new Ganesha(
            call_user_func(
                array($this->configuration['strategyClass'], 'create'),
                $this->configuration
            )
        );
        if ($behaviorOnStorageError = $this->configuration['behaviorOnStorageError']) {
            $ganesha->setBehaviorOnStorageError($behaviorOnStorageError);
        }
        if ($behaviorOnTrip = $this->configuration['behaviorOnTrip']) {
            $ganesha->setBehaviorOnTrip($behaviorOnTrip);
        }

        return $ganesha;
    }
}
