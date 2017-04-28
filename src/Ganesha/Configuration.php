<?php
namespace Ackintosh\Ganesha;

use Ackintosh\Ganesha\Storage\AdapterInterface;

class Configuration implements \ArrayAccess
{
    /**
     * @var array
     */
    private $params = array();

    public function __construct($params)
    {
        $default = array(
            'adapterSetupFunction' => null,
            'behaviorOnCalmedDown' => null,
            'behaviorOnStorageError' => null,
            'behaviorOnTrip' => null,
        );
        $this->params = array_merge($default, $params);
    }

    public function offsetSet($offset, $value)
    {
        $this->params[$offset] = $value;
    }

    public function offsetExists($offset)
    {
        return isset($this->params[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->params[$offset]);
    }

    public function offsetGet($offset)
    {
        return isset($this->params[$offset]) ? $this->params[$offset] : null;
    }

    /**
     * @return callable|\Closure
     */
    public function getAdapterSetupFunction()
    {
        if (isset($this->params['adapter']) && $adapter = $this->params['adapter']) {
            return function () use ($adapter) {
                return $adapter;
            };
        }

        return $this->params['adapterSetupFunction'];
    }
}
