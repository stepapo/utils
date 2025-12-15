<?php

declare(strict_types=1);

namespace Stepapo\Utils;


class ConfigProcessor
{
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
				return self::getValue($params, $m[1]) ?: $value;
			}
		}
		return $value;
	}


	private static function getValue(array $array, string $path, string $sep = '.'): mixed
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
