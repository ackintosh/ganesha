<?php

namespace Ackintosh\Ganesha\Storage;

interface StorageKeysInterface
{
    public function prefix(): string;

    public function success(): string;

    public function failure(): string;

    public function rejection(): string;

    public function lastFailureTime(): string;

    public function status(): string;
}
