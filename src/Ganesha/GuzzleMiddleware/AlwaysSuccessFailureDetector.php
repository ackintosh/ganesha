<?php

namespace Ackintosh\Ganesha\GuzzleMiddleware;

use Psr\Http\Message\ResponseInterface;

class AlwaysSuccessFailureDetector implements FailureDetectorInterface
{
    public function isFailureResponse(ResponseInterface $response): bool
    {
        return false;
    }
}
