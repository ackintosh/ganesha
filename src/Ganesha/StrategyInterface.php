<?php
namespace Ackintosh\Ganesha;

use Ackintosh\Ganesha\Storage\StorageKeysInterface;

interface StrategyInterface
{
    /**
     * @param array $params
     * @throws \LogicException
     */
    public static function validate($params);

    /**
     * @param Configuration $configuration
     * @return mixed
     */
    public static function create(Configuration $configuration);

    /**
     * @param string $service
     * @return void
     */
    public function recordSuccess($service);

    /**
     * @param string $service
     * @return void
     */
    public function recordFailure($service);

    /**
     * @return bool
     */
    public function isAvailable($service);

    /**
     * @return void
     */
    public function reset();
}
