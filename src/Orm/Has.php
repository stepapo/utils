<?php

declare(strict_types=1);

namespace Stepapo\Utils\Orm;

use Nextras\Orm\Collection\Aggregations\AnyAggregator;
use Nextras\Orm\Collection\Aggregations\NoneAggregator;
use Nextras\Orm\Collection\ICollection;


class Has
{
	public static function any(string $expression, $value): array
	{
		$value = $value === null ? [null] : (array) $value;
		if (in_array(null, $value, true)) {
			$value = array_filter($value, fn($v) => $v !== null);
			return count($value) > 0
				? [
					ICollection::OR,
					[$expression => $value],
					Compare::equal($expression, 0),
				] : Compare::equal($expression, 0);
		}
		return [$expression => $value];
	}


	public static function all(string $expression, $value): array
	{
		$result = [ICollection::AND];
		$value = $value === null ? [null] : (array) $value;
		foreach ($value as $v) {
			$result[] = $v === null
				? Compare::equal($expression, 0)
				: [ICollection::AND, new AnyAggregator(Random::generate()), $expression => $v];
		}
		return $result;
	}


	public static function exact(string $expression, $value): array
	{
		$result = static::all($expression, $value);
		$value = $value === null ? [null] : (array) $value;
		$value = array_filter($value, fn($v) => $v !== null);
		$count = count($value);
		if ($count > 0) {
			$result[] = Compare::equal($expression, $count);
		}
		return $result;
	}


	public static function none(string $expression, $value): array
	{
		$value = $value === null ? [null] : (array) $value;
		if (in_array(null, $value, true)) {
			$value = array_filter($value, fn($v) => $v !== null);
			return count($value) > 0
				? [
					ICollection::AND,
					[ICollection::AND, new NoneAggregator, $expression => $value],
					Compare::greater($expression, 0),
				] : Compare::greater($expression, 0);
		}
		return [ICollection::AND, new NoneAggregator, $expression => $value];
	}
}