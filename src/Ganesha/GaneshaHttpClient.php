<?php
namespace Ackintosh\Ganesha;

use Ackintosh\Ganesha;
use Ackintosh\Ganesha\Exception\RejectedException;
use Ackintosh\Ganesha\HttpClient\ServiceNameExtractor;
use Ackintosh\Ganesha\HttpClient\ServiceNameExtractorInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
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
     * @var array<string, mixed>
     */
    private $defaultOptions = [
        // 4xx and 5xx are considered as success by default because server responded
        'ganesha.failure_status_codes' => [], // array - containing HTTP status codes in 4XX and 5XX ranges that
                                              //   should be considered as failure (int value expected)
    ];

    /**
     * @param array<string, mixed> $defaultOptions An array containing valid GaneshaHttpClient options
     */
    public function __construct(
        HttpClientInterface $client,
        Ganesha $ganesha,
        ?ServiceNameExtractorInterface $serviceNameExtractor = null,
        array $defaultOptions = []
    ) {
        $this->client = $client;
        $this->ganesha = $ganesha;
        $this->serviceNameExtractor = $serviceNameExtractor ?: new ServiceNameExtractor();
        $this->defaultOptions = self::mergeDefaultOptions($this->defaultOptions, $defaultOptions);
    }

    /**
     * @param array<string, mixed> $defaultOptions
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private static function mergeDefaultOptions(array $defaultOptions, array $options): array
    {
        return \array_merge($defaultOptions, $options);
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private function avoidGaneshaOptionsPropagation(array $options): array
    {
        $optionsToUnset = array_keys($this->defaultOptions);
        $optionsToUnset[] = ServiceNameExtractor::OPTION_KEY;

        foreach ($optionsToUnset as $optionName) {
            unset($options[$optionName]);
        }

        return $options;
    }

    /**
     * {@inheritdoc}
     *
     * @throws RejectedException when Circuit Breaker is open
     */
    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        $options = self::mergeDefaultOptions($this->defaultOptions, $options);
        $serviceName = $this->serviceNameExtractor->extract($method, $url, $options);

        if (!$this->ganesha->isAvailable($serviceName)) {
            throw new RejectedException(sprintf('"%s" is not available', $serviceName));
        }

        $response = $this->client->request($method, $url, $this->avoidGaneshaOptionsPropagation($options));
        try {
            $response->getHeaders();

            $this->ganesha->success($serviceName);
        } catch (ClientExceptionInterface | ServerExceptionInterface $e) {
            if ($this->isFailureStatusCode($e->getResponse()->getStatusCode(), $options)) {
                $this->ganesha->failure($serviceName);
            } else {
                $this->ganesha->success($serviceName);
            }
        } catch (RedirectionExceptionInterface | TransportExceptionInterface $e) {
            // 3xx when max redirection is reached and network issues are considered as failure
            $this->ganesha->failure($serviceName);
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
     */
    private function isFailureStatusCode(int $responseStatusCode, array $options): bool
    {
        return \in_array($responseStatusCode, $options['ganesha.failure_status_codes'], true);
    }
}
