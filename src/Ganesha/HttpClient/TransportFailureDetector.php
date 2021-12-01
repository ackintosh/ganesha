<?php
namespace Ackintosh\Ganesha\HttpClient;

use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class TransportFailureDetector implements FailureDetectorInterface
{
    /**
     * {@inheritdoc}
     */
    public function getOptionKeys(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function isFailureResponse(ResponseInterface $response, array $requestOptions): bool
    {
        try {
            // Ensure request is triggered
            $response->getContent(true);

            return false;
        } catch (ClientExceptionInterface | ServerExceptionInterface $e) {
            // 4xx and 5xx are considered as success by default because server responded
            return false;
        } catch (RedirectionExceptionInterface | TransportExceptionInterface $e) {
            // 3xx when max redirection is reached and network issues are considered as failure
            return true;
        }
    }
}
