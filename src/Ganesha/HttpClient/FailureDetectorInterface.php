<?php
namespace Ackintosh\Ganesha\HttpClient;

use Symfony\Contracts\HttpClient\ResponseInterface;

interface FailureDetectorInterface
{
    /**
     * @param array<string, mixed> $requestOptions
     */
    public function isFailureResponse(ResponseInterface $response, array $requestOptions): bool;

    /**
     * @return string[]
     */
    public function getOptionKeys(): array;
}
