<?php
namespace Ackintosh\Ganesha;

use Ackintosh\Ganesha;
use Ackintosh\Ganesha\Exception\RejectedException;
use Ackintosh\Ganesha\GuzzleMiddleware\ServiceNameExtractor;
use Ackintosh\Ganesha\GuzzleMiddleware\ServiceNameExtractorInterface;
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
    private $serviceNameExtractor;

    public function __construct(
        Ganesha $ganesha,
        ServiceNameExtractorInterface $serviceNameExtractor = null
    )
    {
        $this->ganesha = $ganesha;
        $this->serviceNameExtractor = $serviceNameExtractor ?: new ServiceNameExtractor();
    }

    /**
     * @param callable $handler
     * @return \Closure
     */
    public function __invoke(callable $handler)
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            $serviceName = $this->serviceNameExtractor->extract($request, $options);

            if (!$this->ganesha->isAvailable($serviceName)) {
                return \GuzzleHttp\Promise\rejection_for(
                    new RejectedException(
                        sprintf('"%s" is not available', $serviceName)
                    )
                );
            }

            $promise = $handler($request, $options);

            return $promise->then(
                function ($value) use ($serviceName) {
                    $this->ganesha->success($serviceName);
                    return \GuzzleHttp\Promise\promise_for($value);
                },
                function ($reason) use ($serviceName) {
                    $this->ganesha->failure($serviceName);
                    return \GuzzleHttp\Promise\rejection_for($reason);
                }
            );
        };
    }
}