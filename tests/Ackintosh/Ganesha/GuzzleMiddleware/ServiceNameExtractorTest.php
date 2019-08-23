<?php
namespace Ackintosh\Ganesha\GuzzleMiddleware;

use GuzzleHttp\Psr7\Request;

class ServiceNameExtractorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function extractFromHeader()
    {
        $this->assertSame(
            'service_name_in_header',
            (new ServiceNameExtractor())->extract($this->request(), [])
        );
    }

    /**
     * @test
     */
    public function extractFromOptions()
    {
        $this->assertSame(
            'service_name_in_option',
            (new ServiceNameExtractor())->extract($this->request(), $this->requestOptions())
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
            (new ServiceNameExtractor())->extract($request, [])
        );
    }

    private function request()
    {
        return new Request(
            'GET',
            'http://api.example.com/awesome_resource',
            [ServiceNameExtractor::HEADER_NAME => 'service_name_in_header']
        );
    }

    private function requestOptions()
    {
        return [
            ServiceNameExtractor::OPTION_KEY => 'service_name_in_option',
        ];
    }
}
