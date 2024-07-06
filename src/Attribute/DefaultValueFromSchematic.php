<?php

declare(strict_types=1);

namespace Stepapo\Utils\Attribute;

use Attribute;


#[Attribute(Attribute::TARGET_PROPERTY)]
class DefaultValueFromSchematic
{
	public function __construct(public string $value) {}
}