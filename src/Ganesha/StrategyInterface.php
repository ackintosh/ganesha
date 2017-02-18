<?php
namespace Ackintosh\Ganesha;

interface StrategyInterface
{
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
}
