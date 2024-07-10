<?php

declare(strict_types=1);

namespace Stepapo\Utils\Attribute;

use Attribute;


#[Attribute(Attribute::TARGET_PROPERTY)]
class CopyValue
{
	public function __construct(public string $from)
	{}
}