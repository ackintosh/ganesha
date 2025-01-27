<?php

declare(strict_types=1);

namespace Ackintosh\Vcr;

use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

final class VcrExtension implements Extension
{
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $facade->registerSubscribers(
            new TestPreparationStartedSubscriber(),
            new TestFinishedSubscriber(),
        );
    }
}
