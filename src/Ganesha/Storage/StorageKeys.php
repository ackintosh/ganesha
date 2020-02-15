<?php
namespace Ackintosh\Ganesha\Storage;

class StorageKeys implements StorageKeysInterface
{
    /**
     * @var string
     */
    const KEY_PREFIX = 'ganesha_';

    /**
     * @var string
     */
    const KEY_SUFFIX_SUCCESS = '_success';

    /**
     * @var string
     */
    const KEY_SUFFIX_FAILURE = '_failure';

    /**
     * @var string
     */
    const KEY_SUFFIX_REJECTION = '_rejection';

    /**
     * @var string
     */
    const KEY_SUFFIX_LAST_FAILURE_TIME = '_last_failure_time';

    /**
     * @var string
     */
    const KEY_SUFFIX_STATUS = '_status';

    /**
     * @return string
     */
    public function prefix(): string
    {
        return self::KEY_PREFIX;
    }

    /**
     * @return string
     */
    public function success(): string
    {
        return self::KEY_SUFFIX_SUCCESS;
    }

    /**
     * @return string
     */
    public function failure(): string
    {
        return self::KEY_SUFFIX_FAILURE;
    }

    /**
     * @return string
     */
    public function rejection(): string
    {
        return self::KEY_SUFFIX_REJECTION;
    }

    /**
     * @return string
     */
    public function lastFailureTime(): string
    {
        return self::KEY_SUFFIX_LAST_FAILURE_TIME;
    }

    /**
     * @return string
     */
    public function status(): string
    {
        return self::KEY_SUFFIX_STATUS;
    }
}
