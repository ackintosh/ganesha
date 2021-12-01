<?php
namespace Ackintosh\Ganesha\HttpClient;

use Symfony\Component\HttpClient\Exception\InvalidArgumentException;

trait HostTrait
{
    public static function extractHostFromUrl(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (false === $host || null === $host) {
            throw new InvalidArgumentException(sprintf('Malformed URL "%s".', $url));
        }

        if (null !== $host) {
            if (!\defined('INTL_IDNA_VARIANT_UTS46') && preg_match('/[\x80-\xFF]/', $host)) {
                throw new InvalidArgumentException(sprintf('Unsupported IDN "%s", try enabling the "intl" PHP extension or running "composer require symfony/polyfill-intl-idn".', $host));
            }

            $host = \defined('INTL_IDNA_VARIANT_UTS46') ? idn_to_ascii($host, \IDNA_DEFAULT, \INTL_IDNA_VARIANT_UTS46) ?: strtolower($host) : strtolower($host);
        }

        return $host;
    }
}
