<?php
namespace Ackintosh\Ganesha\Storage\Adapter;

use APCuIterator;

/**
 * A lightweight wrapper around the functions exposed by the APC user cache
 * module in PHP for testing purposes, since APCu is not available at the
 * command line and thus can't be tested directly by PHPUnit.
 */
class ApcuStore
{
    public function dec($key, $step = 1, &$success = null, $ttl = 0)
    {
        return apcu_dec($key, $step, $success, $ttl);
    }

    public function delete($key)
    {
        return apcu_delete($key);
    }

    public function exists($keys)
    {
        return apcu_exists($keys);
    }

    public function fetch($key, &$success = null)
    {
        return apcu_fetch($key, $success);
    }

    public function inc($key, $step = 1, &$success = null, $ttl = 0)
    {
        return apcu_inc($key, $step, $success, $ttl);
    }

    public function store($key, $var = null, $ttl = 0)
    {
        return apcu_store($key, $var, $ttl);
    }

    public function getIterator(
        $search = null,
        $format = APC_ITER_ALL,
        $chunk_size = 100,
        $list = APC_LIST_ACTIVE
    ): APCuIterator {
        return new APCuIterator(
            $search,
            $format,
            $chunk_size,
            $list
        );
    }
}
