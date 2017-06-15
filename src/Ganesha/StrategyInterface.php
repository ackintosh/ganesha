<?php
namespace Ackintosh\Ganesha;

interface StrategyInterface
{
    /**
     * @param array $params
     * @throws \LogicException
     */
    public static function validate($params);

    /**
     * @param Configuration $configuration
     * @return StrategyInterface
     */
    public static function create(Configuration $configuration);

    /**
     * @param string $resource
     * @return void
     */
    public function recordSuccess($resource);

    /**
     * @param string $resource
     * @return void
     */
    public function recordFailure($resource);

    /**
     * @return bool
     */
    public function isAvailable($resource);

    /**
     * @return void
     */
    public function reset();
}
