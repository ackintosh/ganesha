<?php

namespace Ackintosh\Ganesha\HttpClient;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * @coversDefaultClass \Ackintosh\Ganesha\HttpClient\RestFailureDetector
 */
class RestFailureDetectorTest extends TestCase
{
    /**
     * @test
     * @covers ::getOptionKeys
     */
    public function provideOptionKeys(): void
    {
        self::assertSame(
            ['ganesha.failure_status_codes'],
            (new RestFailureDetector())->getOptionKeys()
        );
    }

    /**
     * @test
     * @covers ::isFailureResponse
     */
    public function networkIssueIsConsideredAsFailure(): void
    {
        $client = new MockHttpClient(
            [
                new MockResponse('', ['error' => 'Network issue']),
            ]
        );
        $response = $client->request('GET', 'https://api.example.com');

        self::assertTrue((new RestFailureDetector())->isFailureResponse($response, []));
    }

    /**
     * @test
     * @dataProvider provide2xxResponseCode
     * @covers ::isFailureResponse
     */
    public function response2xxIsConsideredAsSuccess(int $statusCode): void
    {
        $client = new MockHttpClient(
            [
                new MockResponse('', ['http_code' => $statusCode]),
            ]
        );
        $response = $client->request('GET', 'https://api.example.com');

        self::assertFalse((new RestFailureDetector())->isFailureResponse($response, []));
    }

    /**
     * @return \Generator<int>
     */
    public function provide2xxResponseCode(): \Generator
    {
        yield '200' => [200];
        yield '201' => [201];
        yield '202' => [202];
        yield '203' => [203];
        yield '204' => [204];
        yield '205' => [205];
        yield '206' => [206];
        yield '207' => [207];
        yield '208' => [208];

        yield '210' => [210];

        yield '226' => [226];
    }

    /**
     * @test
     * @dataProvider provide3xxResponseCode
     * @covers ::isFailureResponse
     */
    public function response3xxWithMaxRedirectReachedIsConsideredAsFailure(int $statusCode): void
    {
        $client = new MockHttpClient(
            [
                new MockResponse('', ['http_code' => $statusCode, 'max_redirects' => 0]),
            ]
        );
        $response = $client->request('GET', 'https://api.example.com/'.$statusCode);

        self::assertTrue((new RestFailureDetector())->isFailureResponse($response, []));
    }

    /**
     * @return \Generator<int>
     */
    public function provide3xxResponseCode(): \Generator
    {
        yield '300' => [300];
        yield '301' => [301];
        yield '302' => [302];
        yield '303' => [303];
        yield '304' => [304];
        yield '305' => [305];
        yield '306' => [306];
        yield '307' => [307];
        yield '308' => [308];

        yield '310' => [310];
    }

    /**
     * @test
     * @dataProvider provide4xxResponseCode
     * @covers ::isFailureResponse
     */
    public function response4xxIsConsideredAsSuccessByDefault(int $statusCode): void
    {
        $client = new MockHttpClient(
            [
                new MockResponse('', ['http_code' => $statusCode]),
            ]
        );
        $response = $client->request('GET', 'https://api.example.com');

        self::assertFalse((new RestFailureDetector())->isFailureResponse($response, []));
    }

    /**
     * @return \Generator<int>
     */
    public function provide4xxResponseCode(): \Generator
    {
        yield '400' => [400];
        yield '401' => [401];
        yield '402' => [402];
        yield '403' => [403];
        yield '404' => [404];
        yield '405' => [405];
        yield '406' => [406];
        yield '407' => [407];
        yield '408' => [408];
        yield '409' => [409];
        yield '410' => [410];
        yield '411' => [411];
        yield '412' => [412];
        yield '413' => [413];
        yield '414' => [414];
        yield '415' => [415];
        yield '416' => [416];
        yield '417' => [417];
        yield '418' => [418];

        yield '421' => [421];
        yield '422' => [422];
        yield '423' => [423];
        yield '424' => [424];
        yield '425' => [425];
        yield '426' => [426];

        yield '428' => [428];
        yield '429' => [429];

        yield '431' => [431];

        yield '449' => [449];
        yield '450' => [450];
        yield '451' => [451];

        yield '456' => [456];
    }

    /**
     * @test
     * @dataProvider provide5xxResponseCode
     * @covers ::isFailureResponse
     */
    public function response5xxIsConsideredAsFailureByDefault(int $statusCode): void
    {
        $client = new MockHttpClient(
            [
                new MockResponse('', ['http_code' => $statusCode]),
            ]
        );
        $response = $client->request('GET', 'https://api.example.com');

        self::assertTrue((new RestFailureDetector())->isFailureResponse($response, []));
    }

    /**
     * @return \Generator<int>
     */
    public function provide5xxResponseCode(): \Generator
    {
        yield '500' => [500];
        yield '501' => [501];
        yield '502' => [502];
        yield '503' => [503];
        yield '504' => [504];
        yield '505' => [505];
        yield '506' => [506];
        yield '507' => [507];
        yield '508' => [508];
        yield '509' => [509];
        yield '510' => [510];
        yield '511' => [511];

        yield '520' => [520];
        yield '521' => [521];
        yield '522' => [522];
        yield '523' => [523];
        yield '524' => [524];
        yield '525' => [525];
        yield '526' => [526];
        yield '527' => [527];
    }

    /**
     * @test
     * @dataProvider provideErrorHttpResponseCode
     * @covers ::isFailureResponse
     */
    public function serviceSpecifiedResponseCodeIsConsideredAsFailure(int $statusCode): void
    {
        $statusCodeToConsiderAsFailure = [503];
        $failureExpected = \in_array($statusCode, $statusCodeToConsiderAsFailure, true);

        $client = new MockHttpClient(
            [
                new MockResponse('', ['http_code' => $statusCode]),
            ]
        );
        $response = $client->request('GET', 'https://api.example.com');

        self::assertSame(
            $failureExpected,
            (new RestFailureDetector($statusCodeToConsiderAsFailure))->isFailureResponse($response, [])
        );
    }

    /**
     * @test
     * @dataProvider provideErrorHttpResponseCode
     * @covers ::isFailureResponse
     */
    public function requestSpecifiedResponseCodeIsConsideredAsFailure(int $statusCode): void
    {
        $statusCodeToConsiderAsFailureForTheRequest = [410];
        $failureExpected = \in_array($statusCode, $statusCodeToConsiderAsFailureForTheRequest);

        $client = new MockHttpClient(
            [
                new MockResponse('', ['http_code' => $statusCode]),
            ]
        );
        $response = $client->request('GET', 'https://api.example.com');

        self::assertSame(
            $failureExpected,
            (new RestFailureDetector())->isFailureResponse(
                $response,
                [
                    RestFailureDetector::OPTION_KEY => $statusCodeToConsiderAsFailureForTheRequest,
                ]
            )
        );
    }

    /**
     * @test
     * @dataProvider provideErrorHttpResponseCode
     * @covers ::isFailureResponse
     */
    public function onlyRequestSpecifiedResponseCodeIsConsideredAsFailure(int $statusCode): void
    {
        $statusCodeToConsiderAsFailure = [503];
        $statusCodeToConsiderAsFailureForTheRequest = [410];
        $failureExpected = \in_array($statusCode, $statusCodeToConsiderAsFailureForTheRequest);

        $client = new MockHttpClient(
            [
                new MockResponse('', ['http_code' => $statusCode]),
            ]
        );
        $response = $client->request('GET', 'https://api.example.com');

        self::assertSame(
            $failureExpected,
            (new RestFailureDetector($statusCodeToConsiderAsFailure))->isFailureResponse(
                $response,
                [
                    RestFailureDetector::OPTION_KEY => $statusCodeToConsiderAsFailureForTheRequest,
                ]
            )
        );
    }

    /**
     * @return \Generator<int>
     */
    public function provideErrorHttpResponseCode(): \Generator
    {
        yield from $this->provide4xxResponseCode();
        yield from $this->provide5xxResponseCode();
    }
}
