<?php

declare(strict_types=1);

namespace Stepapo\Utils\Orm;

use Nextras\Orm\Collection\Functions\CountAggregateFunction;
use Nextras\Orm\Collection\Functions\MaxAggregateFunction;
use Nextras\Orm\Collection\Functions\MinAggregateFunction;
use Nextras\Orm\Collection\Functions\SumAggregateFunction;


class Aggregate
{
	public static function count(string $expression): array
	{
		return [CountAggregateFunction::class, $expression];
	}


	public static function max(string $expression): array
	{
		return [MaxAggregateFunction::class, $expression];
	}


	public static function min(string $expression): array
	{
		return [MinAggregateFunction::class, $expression];
	}


	public static function sum(string $expression): array
	{
		return [SumAggregateFunction::class, $expression];
	}
}