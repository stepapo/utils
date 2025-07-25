<?php

declare(strict_types=1);

namespace Stepapo\Utils\Command;

use Stepapo\Utils\Service;


interface Command extends Service
{
	public function run(): int;
}