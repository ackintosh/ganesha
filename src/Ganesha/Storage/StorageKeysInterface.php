<?php
namespace Ackintosh\Ganesha\Storage;


interface StorageKeysInterface
{
    /**
     * @return string
     */
    public function prefix();

    /**
     * @return string
     */
    public function success();

    /**
     * @return string
     */
    public function failure();

    /**
     * @return string
     */
    public function rejection();

    /**
     * @return string
     */
    public function lastFailureTime();

    /**
     * @return string
     */
    public function status();
}
