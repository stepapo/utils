<?php

declare(strict_types=1);

namespace Stepapo\Utils\Attribute;

use Attribute;


#[Attribute(Attribute::TARGET_PROPERTY)]
class ArrayOfType
{
	public function __construct(public string $class, public ?string $keyProperty = null) {}
}