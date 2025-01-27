<?php

declare(strict_types=1);

namespace Ackintosh\Vcr;

use PHPUnit\Event\Test\Finished;
use PHPUnit\Event\Test\FinishedSubscriber;
use VCR\VCR;

final class TestFinishedSubscriber implements FinishedSubscriber
{
    public function notify(Finished $event): void
    {
        VCR::turnOff();
    }
}
