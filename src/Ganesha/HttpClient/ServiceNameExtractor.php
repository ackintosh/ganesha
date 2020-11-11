<?php
namespace Ackintosh\Ganesha\HttpClient;

final class ServiceNameExtractor implements ServiceNameExtractorInterface
{
    use HostTrait;

    /**
     * @var string
     */
    const OPTION_KEY = 'ganesha.service_name';

    /**
     * @var string
     */
    const HEADER_NAME = 'X-Ganesha-Service-Name';

    /**
     * {@inheritdoc}
     */
    public function extract(string $method, string $url, array $requestOptions = []): string
    {
        if (array_key_exists(self::OPTION_KEY, $requestOptions)) {
            return $requestOptions[self::OPTION_KEY];
        }

        $headers = $requestOptions['headers'] ?? [];
        if (array_key_exists(self::HEADER_NAME, $headers)) {
            return $headers[self::HEADER_NAME];
        }

        return self::extractHostFromUrl($url);
    }
}
