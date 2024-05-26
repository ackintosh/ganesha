<?php

namespace Ackintosh\Ganesha\HttpClient;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Ackintosh\Ganesha\HttpClient\ServiceNameExtractor
 */
class ServiceNameExtractorTest extends TestCase
{
    /**
     * @test
     * @covers ::extract
     */
    public function extractFromHeader(): void
    {
        self::assertSame(
            'service_name_in_header',
            (new ServiceNameExtractor())->extract(...self::requestParams(self::requestOptionsWithServiceNameHeader()))
        );
    }

    /**
     * @test
     * @covers ::extract
     */
    public function extractFromOptions(): void
    {
        self::assertSame(
            'service_name_in_option',
            (new ServiceNameExtractor())->extract(...self::requestParams(self::requestOptionsWithServiceNameOption()))
        );
    }

    /**
     * @test
     * @covers ::extract
     */
    public function extractHostnameAsDefault(): void
    {
        self::assertSame(
            'api.example.com',
            (new ServiceNameExtractor())->extract(...self::requestParams([]))
        );
    }

    /**
     * @param array<string, mixed> $requestOptions
     *
     * @return string[]
     */
    private static function requestParams(array $requestOptions = []): array
    {
        return [
            'GET',
            'http://api.example.com/awesome_resource',
            $requestOptions,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function requestOptionsWithServiceNameOption(): array
    {
        return [
            ServiceNameExtractor::OPTION_KEY => 'service_name_in_option',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function requestOptionsWithServiceNameHeader(): array
    {
        return [
            'headers' => [ServiceNameExtractor::HEADER_NAME => 'service_name_in_header']
        ];
    }
}
