<?php
namespace Ackintosh\Ganesha;

use Psr\Http\Message\RequestInterface;

class GuzzleMiddleware
{
    /**
     * @var \Ackintosh\Ganesha
     */
    private $ganesha;

    public function __construct(\Ackintosh\Ganesha $ganesha)
    {
        $this->ganesha = $ganesha;
    }

    /**
     * @param callable $handler
     * @return \Closure
     */
    public function __invoke(callable $handler)
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            return $handler($request, $options);
        };
    }
}