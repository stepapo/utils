<?php

namespace Stepapo\Utils;

use Nette\InvalidArgumentException;
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
			if ($prop->getAttributes(ToArray::class) && isset($config[$prop->getName()])) {
				$config[$prop->getName()] = (array) $config[$prop->getName()];
			}
		}
		$data = (new Processor)->process($schema, $config);
		foreach ($props as $prop) {
			$name = $prop->getName();
			if ($attr = $prop->getAttributes(Type::class)) {
				$class = $attr[0]->getArguments()[0];
				if (isset($config[$name])) {
					$data->$name = $class::createFromArray($config[$name], skipDefaults: $skipDefaults);
				}
			}
			if ($attr = $prop->getAttributes(ArrayOfType::class)) {
				$class = $attr[0]->getArguments()[0];
				foreach ((array) $data->$name as $subKey => $subConfig) {
					$data->$name[$subKey] = $class::createFromArray($subConfig, $subKey, $skipDefaults);
				}
			}
			if ($attr = $prop->getAttributes(CopyValue::class)) {
				$from = $attr[0]->getArguments()[0];
				$data->$name ??= $data->$from;
			}
		}
		return $data;
	}


	protected static function getSchema(bool $skipDefaults = false): ?Schema
	{
		return Expect::fromSchematic(static::class, $skipDefaults);
	}


	protected static function getKeyProperty(): ?string
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


	protected static function getValueProperty(): ?string
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