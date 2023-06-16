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

    /**
     * Function name to be used for returning a rejected promise.
     *
     * @var string
     */
    private string $rejectionForFunction;

    /**
     * Function name to be used for returning a promise.
     *
     * @var string
     */
    private string $promiseForFunction;

    public function __construct(
        Ganesha $ganesha,
        ServiceNameExtractorInterface $serviceNameExtractor = null,
        FailureDetectorInterface $failureDetector = null
    ) {
        $this->ganesha = $ganesha;
        $this->serviceNameExtractor = $serviceNameExtractor ?: new ServiceNameExtractor();
        $this->failureDetector = $failureDetector ?: new AlwaysSuccessFailureDetector();

        // We need to support both the static and function API of `guzzle/promises` for the time being.
        // https://github.com/guzzle/promises#upgrading-from-function-api
        if (class_exists('\GuzzleHttp\Promise\Create')) {
            $this->rejectionForFunction = '\GuzzleHttp\Promise\Create::rejectionFor';
            $this->promiseForFunction = '\GuzzleHttp\Promise\Create::promiseFor';
        } else {
            $this->rejectionForFunction = '\GuzzleHttp\Promise\rejection_for';
            $this->promiseForFunction = '\GuzzleHttp\Promise\promise_for';
        }
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
                return call_user_func(
                    $this->rejectionForFunction,
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
                    return call_user_func(
                        $this->promiseForFunction,
                        $value
                    );
                },
                function ($reason) use ($serviceName) {
                    $this->ganesha->failure($serviceName);
                    return call_user_func(
                        $this->rejectionForFunction,
                        $reason
                    );
                }
            );
        };
    }
}
