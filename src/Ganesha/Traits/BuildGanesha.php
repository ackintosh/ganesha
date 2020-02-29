<?php
namespace Ackintosh\Ganesha\Traits;

use Ackintosh\Ganesha;
use Ackintosh\Ganesha\Configuration;

trait BuildGanesha
{
    /**
     * @throws \InvalidArgumentException
     */
    private function validate(): void
    {
        foreach (self::$requirements as $r) {
            if (!isset($this->params[$r])) {
                throw new \LogicException($r . ' is required');
            }
        }

        if (!call_user_func([$this->params['adapter'], self::$adapterRequirement])) {
            throw new \InvalidArgumentException(get_class($this->params['adapter'])  . ' doesn\'t support expected Strategy: ' . self::$adapterRequirement);
        }
    }

    /**
     * @return Ganesha
     * @throws \InvalidArgumentException
     */
    public function build(): Ganesha
    {
        // Strategy specific validation
        $this->validate();

        $configuration = new Configuration($this->params);
        $configuration->validate();

        return new Ganesha(
            call_user_func(
                [self::$strategyClass, 'create'],
                $configuration
            )
        );
    }
}