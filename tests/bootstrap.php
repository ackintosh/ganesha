<?php
require_once(dirname(__DIR__) . '/vendor/autoload.php');

$vcrConfiguration = \VCR\VCR::configure();
$vcrConfiguration->setCassettePath(__DIR__ . 'VcrFixtures');
