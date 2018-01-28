<?php
require_once(dirname(__DIR__) . '/vendor/autoload.php');

$vcrConfiguration = \VCR\VCR::configure();
$vcrConfiguration->setCassettePath(__DIR__ . '/VcrFixtures');
$vcrConfiguration->enableLibraryHooks([
    'stream_wrapper',
    'curl',
]);
$vcrConfiguration->enableRequestMatchers([
    'method',
    'url',
    'query_string',
    'host',
    'body',
    'post_fields'
]);