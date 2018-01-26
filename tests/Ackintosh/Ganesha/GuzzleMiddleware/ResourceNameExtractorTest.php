<?php
namespace Ackintosh\Ganesha\GuzzleMiddleware;

use GuzzleHttp\Psr7\Request;

class ResourceNameExtractorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function extractFromHeader()
    {
        $this->assertSame(
            'resource_name_in_header',
            (new ResourceNameExtractor())->extract($this->request(), [])
        );
    }

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

    /**
     * @test
     */
    public function extractHostnameAsDefault()
    {
        $request = new Request(
            'GET',
            'http://api.example.com/awesome_resource'
        );
        $this->assertSame(
            'api.example.com',
            (new ResourceNameExtractor())->extract($request, [])
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