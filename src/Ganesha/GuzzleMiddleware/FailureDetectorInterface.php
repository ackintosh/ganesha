<?php

namespace Ackintosh\Ganesha\GuzzleMiddleware;

use Psr\Http\Message\ResponseInterface;

interface FailureDetectorInterface
{
    public function isFailureResponse(ResponseInterface $response): bool;
}
