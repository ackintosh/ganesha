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
        // Validate the params
        Configuration::validate($this->params);

        // Unset `adapter` key from configuration params to avoid circular reference.
        $adapter = $this->params[Configuration::ADAPTER];
        unset($this->params[Configuration::ADAPTER]);

        $configuration = new Configuration($this->params);
        $context = new Ganesha\Context(self::$strategyClass, $adapter, $configuration);

        // AdapterInterface::setConfiguration() is deprecated since 1.2.2. This will be removed in the next major release.
        $adapter->setConfiguration($configuration);
        $adapter->setContext($context);

        return new Ganesha(self::$strategyClass::create($adapter, $configuration));
    }
}
