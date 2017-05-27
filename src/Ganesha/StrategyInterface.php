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
