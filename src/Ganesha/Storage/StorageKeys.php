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
    public function prefix()
    {
        return self::KEY_PREFIX;
    }

    /**
     * @return string
     */
    public function success()
    {
        return self::KEY_SUFFIX_SUCCESS;
    }

    /**
     * @return string
     */
    public function failure()
    {
        return self::KEY_SUFFIX_FAILURE;
    }

    /**
     * @return string
     */
    public function rejection()
    {
        return self::KEY_SUFFIX_REJECTION;
    }

    /**
     * @return string
     */
    public function lastFailureTime()
    {
        return self::KEY_SUFFIX_LAST_FAILURE_TIME;
    }

    /**
     * @return string
     */
    public function status()
    {
        return self::KEY_SUFFIX_STATUS;
    }
}
