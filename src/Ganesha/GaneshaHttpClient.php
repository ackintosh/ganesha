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

    public function __construct(
        HttpClientInterface $client,
        Ganesha $ganesha,
        ?ServiceNameExtractorInterface $serviceNameExtractor = null
    ) {
        $this->client = $client;
        $this->ganesha = $ganesha;
        $this->serviceNameExtractor = $serviceNameExtractor ?: new ServiceNameExtractor();
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

        // Do not propagate option unsupported by decorated client instance
        unset($options[ServiceNameExtractor::OPTION_KEY]);

        $response = $this->client->request($method, $url, $options);
        try {
            $response->getHeaders();

            $this->ganesha->success($serviceName);
        } catch (ClientExceptionInterface | ServerExceptionInterface $e) {
            // 4xx and 5xx are considered as success because server responded
            $this->ganesha->success($serviceName);
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
}
