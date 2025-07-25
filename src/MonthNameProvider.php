<?php

declare(strict_types=1);

namespace Stepapo\Utils;

use Nette\Utils\Strings;


class MonthNameProvider implements Service
{
	public function getNames(string $lang, string $pattern = 'MMM'): array
	{
		$months = [];
		for ($i = 1; $i <= 12; $i++) {
			$date = \DateTime::createFromFormat('!m', (string) $i);
			$formatter = new \IntlDateFormatter($lang, pattern: $pattern);
			$months[$i] = Strings::firstUpper($formatter->format($date));
		}
		return $months;
	}
}