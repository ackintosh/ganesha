<?php
namespace Ackintosh\Ganesha;

use Ackintosh\Ganesha\Storage\AdapterInterface;

class Configuration implements \ArrayAccess
{
    /**
     * @var array
     */
    private $container = array();

    public function __construct($params)
    {
        $this->container = $params;
    }

    public function offsetSet($offset, $value)
    {
        $this->container[$offset] = $value;
    }

    public function offsetExists($offset)
    {
        return isset($this->container[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->container[$offset]);
    }

    public function offsetGet($offset)
    {
        return isset($this->container[$offset]) ? $this->container[$offset] : null;
    }

    /**
     * @throws \LogicException
     * @return void
     */
    public function validate()
    {
        if (
            (isset($this->container['adapter']) && !$this->container['adapter'] instanceof AdapterInterface)
            && !isset($this->container['adapterSetupFunction'])) {
            throw new \LogicException();
        }
    }

    /**
     * @return callable|\Closure
     */
    public function getAdapterSetupFunction()
    {
        if (isset($this->container['adapter']) && $adapter = $this->container['adapter']) {
            return function () use ($adapter) {
                return $adapter;
            };
        }

        return $this->container['adapterSetupFunction'];
    }
}
