<?php
namespace Ackintosh\Ganesha\HttpClient;

use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class RestFailureDetector implements FailureDetectorInterface
{
    /**
     * @var string
     */
    public const OPTION_KEY = 'ganesha.failure_status_codes';

    /**
     * @var int[]
     */
    public const DEFAULT_FAILURE_STATUS_CODES = [
        500, // Internal Server Error
        501, // Not Implemented
        502, // Bad Gateway ou Proxy Error
        503, // Service Unavailable
        504, // Gateway Time-out
        505, // HTTP Version not supported
        506, // Variant Also Negotiates
        507, // Insufficient storage
        508, // Loop detected
        509, // Bandwidth Limit Exceeded
        510, // Not extended
        511, // Network authentication required
        520, // Unknown Error
        521, // Web Server Is Down
        522, // Connection Timed Out
        523, // Origin Is Unreachable
        524, // A Timeout Occurred
        525, // SSL Handshake Failed
        526, // Invalid SSL Certificate
        527, // Railgun Error
    ];

    /**
     * @var int[]
     */
    private $defaultFailureStatusCodes;

    /**
     * @param int[] $defaultFailureStatusCodes
     */
    public function __construct(?array $defaultFailureStatusCodes = null)
    {
        $this->defaultFailureStatusCodes = $defaultFailureStatusCodes ?? self::DEFAULT_FAILURE_STATUS_CODES;
    }

    /**
     * {@inheritdoc}
     */
    public function getOptionKeys(): array
    {
        return [self::OPTION_KEY];
    }

    /**
     * {@inheritdoc}
     */
    public function isFailureResponse(ResponseInterface $response, array $requestOptions): bool
    {
        try {
            // Ensure request is triggered
            $response->getContent();

            return false;
        } catch (ClientExceptionInterface | ServerExceptionInterface $e) {
            return $this->isFailureStatusCode($e->getResponse()->getStatusCode(), $requestOptions);
        } catch (RedirectionExceptionInterface | TransportExceptionInterface $e) {
            // 3xx when max redirection is reached and network issues are considered as failure
            return true;
        }
    }

    /**
     * @param array<string, mixed> $requestOptions
     */
    private function isFailureStatusCode(int $responseStatusCode, array $requestOptions): bool
    {
        $failureStatusCodes = $requestOptions[self::OPTION_KEY] ?? $this->defaultFailureStatusCodes;

        return \in_array($responseStatusCode, $failureStatusCodes, true);
    }
}
