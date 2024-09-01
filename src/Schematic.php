<?php

declare(strict_types=1);

namespace Stepapo\Utils;

use Nette\InvalidArgumentException;
use Nette\Neon\Entity;
use Nette\Neon\Neon;
use Nette\Schema\Processor;
use Nette\Schema\Schema;
use Nette\Utils\ArrayHash;
use Nette\Utils\FileSystem;
use ReflectionClass;
use Stepapo\Utils\Attribute\ArrayOfType;
use Stepapo\Utils\Attribute\CopyValue;
use Stepapo\Utils\Attribute\KeyProperty;
use Stepapo\Utils\Attribute\ToArray;
use Stepapo\Utils\Attribute\Type;
use Stepapo\Utils\Attribute\ValueProperty;


class Schematic extends ArrayHash
{
	public static function createFromNeon(string $file, array $params = [], bool $skipDefaults = false): static
	{
		$config = (array) Neon::decode(FileSystem::read($file));
		$config = ConfigProcessor::process($config, $params);
		return static::createFromArray($config, skipDefaults: $skipDefaults);
	}


	public static function createFromArray(mixed $config = [], mixed $key = null, bool $skipDefaults = false): static
	{
		$schema = static::getSchema($skipDefaults);
		if (!$schema) {
			throw new InvalidArgumentException;
		}
		$valueProperty = static::getValueProperty();
		if ($valueProperty && !isset($config[$valueProperty])) {
			$config = [$valueProperty => $config];
		}
		if ($key) {
			$keyProperty = static::getKeyProperty();
			if ($keyProperty) {
				$config[$keyProperty] = $key;
			}
		}
		$rc = new ReflectionClass(static::class);
		$props = $rc->getProperties();
		foreach ($props as $prop) {
			$name = $prop->getName();
			if (!isset($config[$name])) {
				continue;
			}
			if ($config[$name] instanceof Entity) {
				$rf = new ReflectionClass($config[$name]->value);
				$config[$name] = $rf->newInstance(...$config[$name]->attributes);
			}
			if ($prop->getAttributes(ToArray::class)) {
				$config[$name] = (array) $config[$name];
			}
		}
		$data = (new Processor)->process($schema, $config);
		foreach ($props as $prop) {
			$name = $prop->getName();
			if ($attr = $prop->getAttributes(Type::class)) {
				if (isset($config[$name])) {
					$class = $attr[0]->getArguments()[0];
					$data->$name = $class::createFromArray($config[$name], skipDefaults: $skipDefaults);
				}
			}
			if ($attr = $prop->getAttributes(ArrayOfType::class)) {
				if (isset($config[$name])) {
					$class = $attr[0]->getArguments()[0];
					foreach ((array) $data->$name as $subKey => $subConfig) {
						$data->$name[$subKey] = $class::createFromArray($subConfig, $subKey, $skipDefaults);
					}
				}
			}
			if ($attr = $prop->getAttributes(CopyValue::class)) {
				$from = $attr[0]->getArguments()[0];
				$data->$name ??= $data->$from;
			}
		}
		return $data;
	}


	public static function getSchema(bool $skipDefaults = false): ?Schema
	{
		return Expect::fromSchematic(static::class, $skipDefaults);
	}


	public static function getKeyProperty(): ?string
	{
		$rc = new ReflectionClass(static::class);
		$props = $rc->getProperties();
		foreach ($props as $prop) {
			$name = $prop->getName();
			if ($prop->getAttributes(KeyProperty::class)) {
				return $name;
			}
		}
		return null;
	}


	public static function getValueProperty(): ?string
	{
		$rc = new ReflectionClass(static::class);
		$props = $rc->getProperties();
		foreach ($props as $prop) {
			$name = $prop->getName();
			if ($prop->getAttributes(ValueProperty::class)) {
				return $name;
			}
		}
		return null;
	}
}