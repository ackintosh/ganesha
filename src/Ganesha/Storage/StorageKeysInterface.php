<?php
namespace Ackintosh\Ganesha\Storage;

interface StorageKeysInterface
{
    /**
     * @return string
     */
    public function prefix(): string;

    /**
     * @return string
     */
    public function success(): string;

    /**
     * @return string
     */
    public function failure(): string;

    /**
     * @return string
     */
    public function rejection(): string;

    /**
     * @return string
     */
    public function lastFailureTime(): string;

    /**
     * @return string
     */
    public function status(): string;
}
