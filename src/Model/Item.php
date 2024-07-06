<?php

declare(strict_types=1);

namespace Stepapo\Utils\Model;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionNamedType;
use Stepapo\Utils\ReflectionHelper;
use Stepapo\Utils\Model\Collection;
use Stepapo\Utils\Schematic;


class Item extends Schematic
{
	public function getCollection(string $name): Collection
	{
		$rf = new ReflectionClass($this);
		$prop = $rf->getProperty($name);
		if (!property_exists($this, $name) || !$this->isCollection($name)) {
			throw new InvalidArgumentException;
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