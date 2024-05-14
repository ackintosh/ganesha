<?php

namespace Ackintosh\Ganesha\GuzzleMiddleware;

use Psr\Http\Message\RequestInterface;

interface ServiceNameExtractorInterface
{
    public function extract(RequestInterface $request, array $requestOptions): string;
}
