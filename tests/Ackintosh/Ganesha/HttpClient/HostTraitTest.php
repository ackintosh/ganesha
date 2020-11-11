<?php

namespace Ackintosh\Ganesha\HttpClient;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Exception\InvalidArgumentException;

class HostTraitTest extends TestCase
{
    /**
     * @test
     */
    public function extractsHostFromValidUrl(): void
    {
        $instance = $this->getObjectForTrait(HostTrait::class);

        self::assertEquals('api.example.com', $instance::extractHostFromUrl('http://api.example.com/awesome_resource'));
    }

    /**
     * @test
     */
    public function extractsHostFromValidUnicodeUrl(): void
    {
        if (!\defined('INTL_IDNA_VARIANT_UTS46')) {
            self::markTestSkipped('intl php extension is missing');
        }
        $instance = $this->getObjectForTrait(HostTrait::class);

        self::assertEquals('xn--tst-qla.de', $instance::extractHostFromUrl('http://täst.de/awesome_resource'));
    }

    /**
     * @test
     */
    public function throwsOnUnicodeUrlWithoutIntlExtension(): void
    {
        if (\defined('INTL_IDNA_VARIANT_UTS46')) {
            self::markTestSkipped('intl php extension is installed');
        }
        $instance = $this->getObjectForTrait(HostTrait::class);

        $this->expectException(InvalidArgumentException::class);
        $instance::extractHostFromUrl('http://täst.de/awesome_resource');
    }

    /**
     * @test
     */
    public function throwsOnInvalidUrl(): void
    {
        $instance = $this->getObjectForTrait(HostTrait::class);

        $this->expectException(InvalidArgumentException::class);
        $instance::extractHostFromUrl('bad url');
    }
}
