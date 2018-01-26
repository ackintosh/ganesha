<?php
namespace Ackintosh\Ganesha\GuzzleMiddleware;

use GuzzleHttp\Psr7\Request;

class ResourceNameExtractorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function extractFromOptions()
    {
        $this->assertSame(
            'resource_name_in_option',
            (new ResourceNameExtractor())->extract($this->request(), $this->requestOptions())
        );
    }

    private function request()
    {
        return new Request(
            'GET',
            'http://api.example.com/awesome_resource',
            [ResourceNameExtractor::HEADER_NAME => 'resource_name_in_header']
        );
    }

    private function requestOptions()
    {
        return [
            ResourceNameExtractor::OPTION_KEY => 'resource_name_in_option',
        ];
    }
}