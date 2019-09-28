<?php
namespace Ackintosh\Ganesha;

use Ackintosh\Ganesha\Storage\StorageKeys;

class Configuration implements \ArrayAccess
{
    /**
     * @var array
     */
    private $params;

    public function __construct($params)
    {
        if (!isset($params['storageKeys'])) {
            $params['storageKeys'] = new StorageKeys();
        }
        $this->params = $params;
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
