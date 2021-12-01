<?php
// See https://github.com/FriendsOfPHP/PHP-CS-Fixer for more information

$finder = PhpCsFixer\Finder::create()
    ->exclude('tests/VcrFixtures')
    ->in(__DIR__)
;

$config = new PhpCsFixer\Config();
return $config->setRiskyAllowed(true)
    ->setRules([
        '@PSR2' => true,
    ])
    ->setFinder($finder)
;
