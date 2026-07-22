<?php

declare(strict_types=1);

namespace Stepapo\Utils;

use Nette\InvalidArgumentException;
use function array_key_exists, is_array, is_string;


class ConfigProcessor
{
	public static function processIncludes(string $file, mixed $value, array $params): mixed
	{
		if (is_array($value)) {
			$return = [];
			foreach ($value as $k => $v) {
				$return[$k] = self::processIncludes($file, $v, $params);
			}
			return $return;
		}
		if (is_string($value)) {
			preg_match('/^~(.+)$/', $value, $m);
			if (isset($m[1])) {
				if (file_exists($f = dirname($file) . '/' . $m[1])) {
					return Config::neonToArray($f, $params);
				} else {
					throw new InvalidArgumentException("File '$f' does not exist.");
				}
			}
		}
		return $value;
	}


	public static function process(mixed $value, mixed $params): mixed
	{
		if (is_array($value)) {
			$return = [];
			foreach ($value as $k => $v) {
				$return[self::process($k, $params)] = self::process($v, $params);
			}
			return $return;
		}
		if (is_string($value)) {
			preg_match('/^%(.*)%$/', $value, $m);
			if (isset($m[1])) {
				if (array_key_exists($m[1], $params)) {
					return $params[$m[1]];
				}
				if ($paramValue = self::getValue($params, $m[1])) {
					return $paramValue;
				} else {
					//throw new InvalidArgumentException("Parameter '$m[1]' is not defined.");
				}
			}
			preg_match('/^\$(.*)$/', $value, $m);
			if (isset($m[1])) {
				if (array_key_exists($m[1], $params)) {
					return $params[$m[1]];
				}
				if ($paramValue = self::getValue($params, $m[1])) {
					return $paramValue;
				} else {
					//throw new InvalidArgumentException("Parameter '$m[1]' is not defined.");
				}
			}
		}
		return $value;
	}


	/**
	 * @param non-empty-string $sep
	 */
	private static function getValue(mixed $array, string $path, string $sep = '.'): mixed
	{
		$keys = $path === '' ? [] : explode($sep, $path);
		foreach ($keys as $key) {
			if (!is_array($array) || !array_key_exists($key, $array)) {
				return null;
			}
			$array = $array[$key];
		}
		return $array;
	}
}
