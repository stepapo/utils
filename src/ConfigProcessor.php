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
				return array_key_exists($m[1], $params) ? $params[$m[1]] : $value;
			}
		}
		return $value;
	}
}
