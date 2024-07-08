<?php

declare(strict_types=1);

namespace Stepapo\Utils\Model;

use InvalidArgumentException;
use ReflectionClass;
use Stepapo\Utils\ReflectionHelper;
use Stepapo\Utils\Schematic;


class Item extends Schematic
{
	public function getCollection(string $name): Collection
	{
		$rf = new ReflectionClass($this);
		$prop = $rf->getProperty($name);
		if (!property_exists($this, $name) || !$this->isCollection($name)) {
			throw new InvalidArgumentException("Property '$name' does not exist or is not a collection.");
		}
		return new Collection($prop->isInitialized($this) ? $this->$name : []);
	}


	public function isCollection(string $name): bool
	{
		$rf = new ReflectionClass($this);
		$prop = $rf->getProperty($name);
		return ReflectionHelper::propertyHasType($prop, 'array');
	}
}