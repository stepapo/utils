<?php

declare(strict_types=1);

namespace Stepapo\Utils;

use Nette\Schema\Elements\Structure;
use Nette\Schema\Elements\Type;
use Nette\Schema\Helpers;
use Nette\Utils\Validators;
use ReflectionClass;
use Stepapo\Utils\Attribute\DefaultFromSchematic;
use Stepapo\Utils\Attribute\DefaultValue;


class Expect
{
	public static function fromSchematic(string $schematic, bool $skipDefaults = false, array $items = []): Structure
	{
		$rc = new ReflectionClass($schematic);
		$props = $rc->getProperties();

		foreach ($props as $prop) {
			$item = &$items[$prop->getName()];
			$type = Helpers::getPropertyType($prop) ?? 'mixed';
			$item = new Type($type);
			if ($prop->hasDefaultValue()) {
				$def = $prop->getDefaultValue();
			} elseif ($attr = $prop->getAttributes(DefaultValue::class)) {
				$def = $attr[0]->getArguments()[0];
			} elseif ($attr = $prop->getAttributes(DefaultFromSchematic::class)) {
				$schematic = $attr[0]->getArguments()[0];
				$def = $schematic::createFromArray();
			} else {
				$def = null;
			}
			if ($def === null) {
				if (Validators::is(null, $type)) {
					$item->default(null);
				} else if (!$skipDefaults) {
					$item->required();
				}
			} else {
				$item->default($def);
			}
		}

		return (new Structure($items))->skipDefaults($skipDefaults)->castTo($rc->getName());
	}
}