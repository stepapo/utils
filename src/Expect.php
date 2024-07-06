<?php

declare(strict_types=1);

namespace Stepapo\Utils;

use Nette\Schema\Elements\Structure;
use Nette\Schema\Elements\Type;
use Nette\Schema\Helpers;
use Nette\Utils\Validators;
use ReflectionClass;
use Stepapo\Utils\Attribute\DefaultValue;
use Stepapo\Utils\Attribute\DefaultValueFromSchematic;
use Webovac\Core\Model\CmsDataRepository;


class Expect
{
	public static function fromSchematic(string $class, string $mode, array $items = []): Structure
	{
		$rc = new ReflectionClass($class);
		$props = $rc->getProperties();

		foreach ($props as $prop) {
			$item = &$items[$prop->getName()];
			$type = Helpers::getPropertyType($prop) ?? 'mixed';
			$item = new Type($type);
			if ($prop->hasDefaultValue()) {
				$def = $prop->getDefaultValue();
			} elseif ($attr = $prop->getAttributes(DefaultValue::class)) {
				$def = $attr[0]->getArguments()[0];
			} elseif ($attr = $prop->getAttributes(DefaultValueFromSchematic::class)) {
				$class = $attr[0]->getArguments()[0];
				$def = $class::createFromArray([], $mode);
			} else {
				$def = null;
			}
			if ($def === null) {
				if (Validators::is(null, $type)) {
					$item->default(null);
				} elseif (ReflectionHelper::propertyHasType($prop, 'bool')) {
					$item->default(false);
				} elseif (ReflectionHelper::propertyHasType($prop, 'string')) {
					$item->default('');
				} elseif (ReflectionHelper::propertyHasType($prop, 'int') || ReflectionHelper::propertyHasType($prop, 'float')) {
					$item->default(0);
				} elseif (ReflectionHelper::propertyHasType($prop, 'array')) {
					$item->default([]);
				} else {
					$item->required();
				}
			} else {
				$item->default($def);
			}
		}

		return (new Structure($items))->skipDefaults($mode === CmsDataRepository::MODE_UPDATE)->castTo($rc->getName());
	}
}