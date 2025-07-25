<?php

declare(strict_types=1);

namespace Stepapo\Utils;

use ReflectionNamedType;
use ReflectionProperty;
use ReflectionUnionType;


class ReflectionHelper
{
	public static function propertyHasType(ReflectionProperty $prop, string|array $types): bool
	{
		foreach ((array) $types as $t) {
			if ($prop->getType() instanceof ReflectionNamedType) {
				if ($prop->getType()->getName() === $t) {
					return true;
				}
			} elseif ($prop->getType() instanceof ReflectionUnionType) {
				foreach ($prop->getType()->getTypes() as $type) {
					if ($type->getName() === $t) {
						return true;
					}
				}
			}
		}
		return false;
	}
}