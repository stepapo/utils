<?php

declare(strict_types=1);

namespace Stepapo\Utils\Latte;


class Filters
{
	public static function intlDate(\DateTimeInterface $time, string $pattern, ?string $locale = null): ?string
	{
		$formatter = new \IntlDateFormatter(
			$locale ?: setlocale(LC_TIME, 0),
			\IntlDateFormatter::LONG,
			\IntlDateFormatter::LONG
		);
		$formatter->setPattern($pattern);
		return $formatter->format($time);
	}


	public static function plural(int $count, string $first, string $second, string $third): string
	{
		if ($count === 0 || $count > 4) {
			return $third;
		}
		if ($count === 1) {
			return $first;
		}
		return $second;
	}


	public static function monthName(int $monthNumber, ?string $locale = null): string
	{
		$dateTime = \DateTime::createFromFormat('!m', (string) $monthNumber);
		return static::intlDate($dateTime, 'MMM', $locale);
	}


	public static function duration(float $duration): string
	{
		$date = new \DateTime('@' . ($duration ?: 0));
		return $date->format('H:i:s');
	}
}
