<?php
namespace Ackintosh\Ganesha;

use Ackintosh\Ganesha;
use Ackintosh\Ganesha\Exception\RejectedException;
use Ackintosh\Ganesha\GuzzleMiddleware\AlwaysSuccessFailureDetector;
use Ackintosh\Ganesha\GuzzleMiddleware\FailureDetectorInterface;
use Ackintosh\Ganesha\GuzzleMiddleware\ServiceNameExtractor;
use Ackintosh\Ganesha\GuzzleMiddleware\ServiceNameExtractorInterface;
use Psr\Http\Message\RequestInterface;

class GuzzleMiddleware
{
    /**
     * @var \Ackintosh\Ganesha
     */
    private $ganesha;

    /**
     * @var ServiceNameExtractorInterface
     */
    private $serviceNameExtractor;

    /**
     * @var FailureDetectorInterface
     */
    private $failureDetector;

    public function __construct(
        Ganesha $ganesha,
        ServiceNameExtractorInterface $serviceNameExtractor = null,
        FailureDetectorInterface $failureDetector = null
    ) {
        $this->ganesha = $ganesha;
        $this->serviceNameExtractor = $serviceNameExtractor ?: new ServiceNameExtractor();
        $this->failureDetector = $failureDetector ?: new AlwaysSuccessFailureDetector();
    }

    /**
     * @param callable $handler
     * @return \Closure
     */
    public function __invoke(callable $handler): \Closure
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            $serviceName = $this->serviceNameExtractor->extract($request, $options);

            if (!$this->ganesha->isAvailable($serviceName)) {
                return \GuzzleHttp\Promise\Create::rejectionFor(
                    new RejectedException(
                        sprintf('"%s" is not available', $serviceName)
                    )
                );
            }

            $promise = $handler($request, $options);

            return $promise->then(
                function ($value) use ($serviceName) {
                    if ($this->failureDetector->isFailureResponse($value)) {
                        $this->ganesha->failure($serviceName);
                    } else {
                        $this->ganesha->success($serviceName);
                    }
                    return \GuzzleHttp\Promise\Create::promiseFor($value);
                },
                function ($reason) use ($serviceName) {
                    $this->ganesha->failure($serviceName);
                    return \GuzzleHttp\Promise\Create::rejectionFor($reason);
                }
            );
        };
    }
}
