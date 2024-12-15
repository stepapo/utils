<?php

declare(strict_types=1);

namespace Stepapo\Utils\Orm;

use Nextras\Orm\Collection\Functions\CompareEqualsFunction;
use Nextras\Orm\Collection\Functions\CompareGreaterThanEqualsFunction;
use Nextras\Orm\Collection\Functions\CompareGreaterThanFunction;
use Nextras\Orm\Collection\Functions\CompareNotEqualsFunction;
use Nextras\Orm\Collection\Functions\CompareSmallerThanEqualsFunction;
use Nextras\Orm\Collection\Functions\CompareSmallerThanFunction;


class Compare
{
	public static function equals(string $expression, $value): array
	{
		return [CompareEqualsFunction::class, $expression, $value];
	}


	public static function greaterThanOrEquals(string $expression, $value): array
	{
		return [CompareGreaterThanEqualsFunction::class, $expression, $value];
	}


	public static function greaterThan(string $expression, $value): array
	{
		return [CompareGreaterThanFunction::class, $expression, $value];
	}


	public static function notEquals(string $expression, $value): array
	{
		return [CompareNotEqualsFunction::class, $expression, $value];
	}


	public static function smallerThanOrEquals(string $expression, $value): array
	{
		return [CompareSmallerThanEqualsFunction::class, $expression, $value];
	}


	public static function smallerThan(string $expression, $value): array
	{
		return [CompareSmallerThanFunction::class, $expression, $value];
	}
}