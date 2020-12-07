<?php
namespace Ackintosh\Ganesha\HttpClient;

interface ServiceNameExtractorInterface
{
    /**
     * @param array<string, mixed> $requestOptions
     */
    public function extract(string $method, string $url, array $requestOptions = []): string;
}
