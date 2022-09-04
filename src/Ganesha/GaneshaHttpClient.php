<?php
namespace Ackintosh\Ganesha;

use Ackintosh\Ganesha;
use Ackintosh\Ganesha\Exception\RejectedException;
use Ackintosh\Ganesha\HttpClient\FailureDetectorInterface;
use Ackintosh\Ganesha\HttpClient\ServiceNameExtractor;
use Ackintosh\Ganesha\HttpClient\ServiceNameExtractorInterface;
use Ackintosh\Ganesha\HttpClient\TransportFailureDetector;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

final class GaneshaHttpClient implements HttpClientInterface
{
    /**
     * @var HttpClientInterface
     */
    private $client;

    /**
     * @var Ganesha
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
     * @param array<string, mixed> $defaultOptions An array containing valid GaneshaHttpClient options
     */
    public function __construct(
        HttpClientInterface $client,
        Ganesha $ganesha,
        ?ServiceNameExtractorInterface $serviceNameExtractor = null,
        ?FailureDetectorInterface $failureDetector = null
    ) {
        $this->client = $client;
        $this->ganesha = $ganesha;
        $this->serviceNameExtractor = $serviceNameExtractor ?: new ServiceNameExtractor();
        $this->failureDetector = $failureDetector ?: new TransportFailureDetector();
    }

    /**
     * {@inheritdoc}
     *
     * @throws RejectedException when Circuit Breaker is open
     */
    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        $serviceName = $this->serviceNameExtractor->extract($method, $url, $options);

        if (!$this->ganesha->isAvailable($serviceName)) {
            throw new RejectedException(sprintf('"%s" is not available', $serviceName));
        }

        $response = $this->client->request($method, $url, $this->avoidGaneshaOptionsPropagation($options));
        if ($this->failureDetector->isFailureResponse($response, $options)) {
            $this->ganesha->failure($serviceName);
        } else {
            $this->ganesha->success($serviceName);
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function stream($responses, float $timeout = null): ResponseStreamInterface
    {
        return $this->client->stream($responses, $timeout);
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private function avoidGaneshaOptionsPropagation(array $options): array
    {
        $optionsToUnset = $this->failureDetector->getOptionKeys();
        // FIXME: ServiceNameExtractorInterface implementation should be able to provide its own options keys
        $optionsToUnset[] = ServiceNameExtractor::OPTION_KEY;

        foreach ($optionsToUnset as $optionName) {
            unset($options[$optionName]);
        }

        return $options;
    }

    /**
     * {@inheritdoc}
     */
    public function withOptions(array $options): static
    {
        $clone = clone $this;
        $clone->client = $this->client->withOptions($options);

        return $clone;
    }
}
