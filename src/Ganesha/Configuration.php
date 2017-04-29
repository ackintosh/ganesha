<?php
namespace Ackintosh\Ganesha;

class Configuration implements \ArrayAccess
{
    /**
     * @var array
     */
    private $params;

    public function __construct($params)
    {
        $default = array(
            'onCalmedDown'      => null,
            'onStorageError'    => null,
            'onTrip'            => null,
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
}
