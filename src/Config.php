<?php

declare(strict_types=1);

namespace Stepapo\Utils;

use Nette\InvalidArgumentException;
use Nette\Neon\Entity;
use Nette\Neon\Neon;
use Nette\Schema\Elements\Structure;
use Nette\Schema\Helpers;
use Nette\Schema\Processor;
use Nette\Schema\Schema;
use Nette\Utils\ArrayHash;
use Nette\Utils\FileSystem;
use Nette\Utils\Validators;
use ReflectionClass;
use Stepapo\Restful\Utils\Strings;
use Stepapo\Utils\Attribute\ArrayOfType;
use Stepapo\Utils\Attribute\CopyValue;
use Stepapo\Utils\Attribute\DefaultFromConfig;
use Stepapo\Utils\Attribute\DefaultValue;
use Stepapo\Utils\Attribute\KeyProperty;
use Stepapo\Utils\Attribute\SkipInComparison;
use Stepapo\Utils\Attribute\ToArray;
use Stepapo\Utils\Attribute\Type;
use Stepapo\Utils\Attribute\ValueProperty;
use Tracy\Dumper;


class Config extends ArrayHash
{
	protected static function getExtensionName(): ?string
	{
		return null;
	}


	public static function createFromNeon(string $file, array $params = [], bool $skipDefaults = false): static
	{
		return static::createFromArray(static::neonToArray($file, $params), skipDefaults: $skipDefaults);
	}


	public static function neonToArray(string $file, array $params = []): array
	{
		$config = (array) Neon::decode(FileSystem::read($file));
		$extName = static::getExtensionName();
		if ($extName && isset($config[$extName])) {
			$config = $config[$extName];
		}
		return ConfigProcessor::process($config, $params);
	}


	public static function createFromArray(mixed $config = [], mixed $key = null, bool $skipDefaults = false, mixed $parentKey = null): static
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
			if ($keyProperty && !isset($config[$keyProperty])) {
				$config[$keyProperty] = $key;
			}
		}
		$rc = new ReflectionClass(static::class);
		$props = $rc->getProperties();
		foreach ($props as $prop) {
			$name = $prop->getName();
			if (!array_key_exists($name, $config)) {
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
		foreach ($props as $prop) {
			$name = $prop->getName();
			if ($attr = $prop->getAttributes(Type::class)) {
				if (isset($config[$name]) && !is_numeric($config[$name])) {
					$class = $attr[0]->getArguments()[0];
					$config[$name] = $class::createFromArray($config[$name], skipDefaults: $skipDefaults, parentKey: $key);
				}
			}
			if ($attr = $prop->getAttributes(ArrayOfType::class)) {
				if (isset($config[$name])) {
					$class = $attr[0]->getArguments()[0];
					foreach ((array) $config[$name] as $subKey => $subConfig) {
						$subData = $class::createFromArray($subConfig, $subKey, $skipDefaults, parentKey: $key);
						$subKeyProperty = $subData::getKeyProperty();
						if ($subKeyProperty) {
							unset($config[$name][$subKey]);
							$subKey = $subData->{$subKeyProperty};
						}
						$config[$name][$subKey] = $subData;
					}
				}
			}
			if ($attr = $prop->getAttributes(CopyValue::class)) {
				$from = $attr[0]->getArguments()[0];
				$config[$name] ??= $config[$from];
			}
		}
		$data = (new Processor)->process($schema, $config);
		if ($rc->hasMethod('process')) {
			$data->process($parentKey);
		}
		return $data;
	}


	public static function getSchema(bool $skipDefaults = false): ?Schema
	{
		$rc = new ReflectionClass(static::class);
		$props = $rc->getProperties();
		$items = [];

		foreach ($props as $prop) {
			$type = Helpers::getPropertyType($prop) ?? 'mixed';
			$item = new \Nette\Schema\Elements\Type($type);
			$items[$prop->getName()] = $item;
			if ($prop->hasDefaultValue()) {
				$def = $prop->getDefaultValue();
			} elseif ($defaultValue = $prop->getAttributes(DefaultValue::class)) {
				if ($t = $prop->getAttributes(Type::class)) {
					$type = $t;
					$config = $type[0]->getArguments()[0];
					$def = $config::createFromArray($defaultValue[0]->getArguments()[0]);
				} else {
					$def = $defaultValue[0]->getArguments()[0];
				}
			} elseif ($attr = $prop->getAttributes(DefaultFromConfig::class)) {
				$config = $attr[0]->getArguments()[0];
				$def = $config::createFromArray();
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
				$item->default($def)->mergeDefaults(false);
			}
		}

		return (new Structure($items))->skipDefaults($skipDefaults)->castTo($rc->getName());
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
			if ($prop->getAttributes(ValueProperty::class) && !$prop->getAttributes(KeyProperty::class)) {
				return $name;
			}
		}
		return null;
	}


	public function isSameAs(Config $other): bool
	{
		if (!$other instanceof self) {
			throw new InvalidArgumentException;
		}
		$rc = new ReflectionClass($this);
		$props = $rc->getProperties();
		foreach ($props as $prop) {
			if ($prop->getAttributes(SkipInComparison::class)) {
				continue;
			}
			$name = $prop->getName();
			if (!isset($this->{$name}) xor !isset($other->{$name})) {
				return false;
			}
			if (!isset($this->{$name}) && !isset($this->{$name})) {
				continue;
			}
			if (!$this->areSame($this->{$name}, $other->{$name})) {
				return false;
			}
		}
		return true;
	}


	private function areSame(mixed $one, mixed $two): bool
	{
		if ($one instanceof \DateTimeInterface) {
			if ($one != $two) {
				return false;
			}
		} elseif ($one instanceof Config) {
			if (!$one->isSameAs($two)) {
				return false;
			}
		} elseif (is_array($one) && is_array($two)) {
			sort($one);
			sort($two);
			return $one == $two;
		} elseif ($one !== $two) {
			return false;
		}
		return true;
	}
}