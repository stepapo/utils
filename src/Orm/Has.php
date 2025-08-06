<?php

declare(strict_types=1);

namespace Stepapo\Utils\Orm;

use Nette\Utils\Random;
use Nextras\Orm\Collection\Aggregations\AnyAggregator;
use Nextras\Orm\Collection\Aggregations\NoneAggregator;
use Nextras\Orm\Collection\ICollection;


class Has
{
	public static function any(string $expression, mixed $value): array
	{
		$value = $value === null ? [null] : (array) $value;
		if (in_array(null, $value, true)) {
			$value = array_filter($value, fn($v) => $v !== null);
			return count($value) > 0
				? [
					ICollection::OR,
					[$expression => $value],
					Compare::equals(Aggregate::count($expression), 0),
				] : Compare::equals(Aggregate::count($expression), 0);
		}
		return [$expression => $value];
	}


	public static function all(string $expression, mixed $value): array
	{
		$result = [ICollection::AND];
		$value = $value === null ? [null] : (array) $value;
		foreach ($value as $v) {
			$result[] = $v === null
				? Compare::equals(Aggregate::count($expression), 0)
				: [ICollection::AND, new AnyAggregator(Random::generate()), $expression => $v];
		}
		return $result;
	}


	public static function exact(string $expression, mixed $value): array
	{
		$result = static::all($expression, $value);
		$value = $value === null ? [null] : (array) $value;
		$value = array_filter($value, fn($v) => $v !== null);
		$count = count($value);
		if ($count > 0) {
			$result[] = Compare::equals(Aggregate::count($expression), $count);
		}
		return $result;
	}


	public static function none(string $expression, mixed $value): array
	{
		$value = $value === null ? [null] : (array) $value;
		if (in_array(null, $value, true)) {
			$value = array_filter($value, fn($v) => $v !== null);
			return count($value) > 0
				? [
					ICollection::AND,
					[ICollection::AND, new NoneAggregator, $expression => $value],
					Compare::greaterThan(Aggregate::count($expression), 0),
				] : Compare::greaterThan(Aggregate::count($expression), 0);
		}
		return [ICollection::AND, new NoneAggregator, $expression => $value];
	}
}