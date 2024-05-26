<?php

require_once(dirname(__DIR__) . '/vendor/autoload.php');

error_reporting(E_ALL);

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
