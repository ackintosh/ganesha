<?php

declare(strict_types=1);

namespace Ackintosh\Vcr;

use PHPUnit\Event\Code\TestMethod;
use PHPUnit\Event\Test\PreparationStarted;
use PHPUnit\Event\Test\PreparationStartedSubscriber;
use VCR\VCR;

final class TestPreparationStartedSubscriber implements PreparationStartedSubscriber
{
    public function notify(PreparationStarted $event): void
    {
        $testMethod = $event->test();
        if (!$testMethod instanceof TestMethod) {
            return;
        }

        $class = $testMethod->className();
        $method = $testMethod->name();

        if (!method_exists($class, $method)) {
            return;
        }

        $reflection = new \ReflectionMethod($class, $method);
        $docBlock = $reflection->getDocComment();

        // Use regex to parse the doc_block for a specific annotation
        $parsed = self::parseDocBlock($docBlock, '@vcr');
        $cassetteName = array_pop($parsed);

        if (empty($cassetteName)) {
            return;
        }

        // If the cassette name ends in .json, then use the JSON storage format
        if (str_ends_with($cassetteName, '.json')) {
            VCR::configure()->setStorage('json');
        }

        VCR::turnOn();
        VCR::insertCassette($cassetteName);
    }

    private static function parseDocBlock($docBlock, $tag): array
    {
        $matches = [];

        if (empty($docBlock)) {
            return $matches;
        }

        $regex = "/{$tag} (.*)(\\r\\n|\\r|\\n)/U";
        preg_match_all($regex, $docBlock, $matches);

        if (empty($matches[1])) {
            return array();
        }

        // Removed extra index
        $matches = $matches[1];

        // Trim the results, array item by array item
        foreach ($matches as $ix => $match) {
            $matches[$ix] = trim($match);
        }

        return $matches;
    }
}
