<?php
namespace Ackintosh\Ganesha\GuzzleMiddleware;

use Psr\Http\Message\RequestInterface;

interface ServiceNameExtractorInterface
{
    /**
     * @param RequestInterface $request
     * @param array $requestOptions
     * @return string
     */
    public function extract(RequestInterface $request, array $requestOptions);
}
