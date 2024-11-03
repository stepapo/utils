<?php

declare(strict_types=1);

namespace Stepapo\Utils\Command;


interface Command
{
	public function run(): int;
}