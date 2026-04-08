<?php

namespace Ackintosh\Ganesha;

use DateTimeImmutable;
use Psr\Clock\ClockInterface;

class NativeClock implements ClockInterface
{
	public function now(): DateTimeImmutable
	{
		return new DateTimeImmutable();
	}
}