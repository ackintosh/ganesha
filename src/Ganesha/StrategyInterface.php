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
     * @param string $serviceName
     * @return void
     */
    public function recordSuccess($serviceName);

    /**
     * @param string $serviceName
     * @return void
     */
    public function recordFailure($serviceName);

    /**
     * @return bool
     */
    public function isAvailable($serviceName);

    /**
     * @return void
     */
    public function reset();
}
