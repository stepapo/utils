<?php

declare(strict_types=1);

namespace Stepapo\Utils;

use Nette\Utils\Arrays;


class ConvertArray
{
	/** @returns int[] */
	public static function toInt(array $array): array
	{
		return Arrays::map($array, fn(mixed $v) => (int) $v);
	}
}