<?php
namespace Ackintosh\Ganesha;

use Ackintosh\Ganesha;
use Ackintosh\Ganesha\GuzzleMiddleware\ResourceNameExtractor;
use Ackintosh\Ganesha\GuzzleMiddleware\ResourceNameExtractorInterface;
use Psr\Http\Message\RequestInterface;

class GuzzleMiddleware
{
    /**
     * @var \Ackintosh\Ganesha
     */
    private $ganesha;

    /*
     * @var ResourceNameExtractorInterface
     */
    private $reresourceNameExtractor;

    public function __construct(
        Ganesha $ganesha,
        ResourceNameExtractorInterface $resourceNameExtractor = null
    )
    {
        $this->ganesha = $ganesha;
        $this->reresourceNameExtractor = $resourceNameExtractor ?: new ResourceNameExtractor();
    }

    /**
     * @param callable $handler
     * @return \Closure
     */
    public function __invoke(callable $handler)
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            $resourceName = $this->reresourceNameExtractor->extract($request, $options);
            $promise = $handler($request, $options);

            return $promise->then(
                function ($value) use ($resourceName) {
                    $this->ganesha->success($resourceName);
                    return \GuzzleHttp\Promise\promise_for($value);
                },
                function ($reason) use ($resourceName) {
                    $this->ganesha->failure($resourceName);
                    return \GuzzleHttp\Promise\rejection_for($reason);
                }
            );
        };
    }
}