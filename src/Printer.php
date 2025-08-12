<?php

declare(strict_types=1);

namespace Stepapo\Utils;


use Stepapo\Model\Orm\DiffList;

class Printer
{
	public function printSeparator(string $color = 'silver'): void
	{
		echo $this->color($color, '------------------------------------------------------------') . PHP_EOL;
	}


	public function printBigSeparator(string $color = 'silver'): void
	{
		echo $this->color($color, '============================================================') . PHP_EOL;
	}


	public function printLine(string $text, string $color = 'silver'): void
	{
		echo $this->color($color, $text) . PHP_EOL;
	}


	public function printText(string $text, string $color = 'silver'): void
	{
		echo $this->color($color, $text);
	}


	public function printDiff(DiffList $diff, int $tabCount = 0): void
	{
		if (isset($diff->propertyList)) {
			foreach ($diff->propertyList as $key => $value) {
				for ($i = 0; $i < $tabCount; $i++) {
					$this->printText("| ", 'gray');
				}
				$this->printText((string) $key, $key === 'removedCount' ? 'maroon' : 'olive');
				if (isset($value->entityList) || isset($value->propertyList)) {
					$this->printText(':');
					$this->printText("\n");
					$this->printDiff($value, $tabCount + 1);
				} else {
					$this->printText(': ');
					if ($key === 'removedCount') {
						$this->printText((string) $value);
					} else {
						$this->printText(($value['old'] ? ($value['old'] . ' -> ') : '') . $value['new']);
					}
					$this->printText("\n");
				}
			}
		}
		if (isset($diff->entityList)) {
			foreach ($diff->entityList as $key => $value) {
				for ($i = 0; $i < $tabCount; $i++) {
					$this->printText('| ', 'gray');
				}
				$this->printText((string) $key, 'teal');
				$this->printText(':');
				$this->printText("\n");
				$this->printDiff($value, $tabCount + 1);
			}
		}
	}


	public function printDone(): void
	{
		echo $this->color('aqua', ' Done') . PHP_EOL;
	}


	public function printOk(): void
	{
		echo $this->color('lime', ' OK') . PHP_EOL;
	}


	public function printError(): void
	{
		echo $this->color('red', ' ERROR') . PHP_EOL;
	}


	public function color(string $color = '', ?string $s = null): string
	{
		$colors = [
			'black' => '0;30', 'gray' => '1;30', 'silver' => '0;37', 'white' => '1;37',
			'navy' => '0;34', 'blue' => '1;34', 'green' => '0;32', 'lime' => '1;32',
			'teal' => '0;36', 'aqua' => '1;36', 'maroon' => '0;31', 'red' => '1;31',
			'purple' => '0;35', 'fuchsia' => '1;35', 'olive' => '0;33', 'yellow' => '1;33',
			null => '0',
		];
		$c = explode('/', $color);
		return "\e["
			. str_replace(';', "m\e[", $colors[$c[0]] . (empty($c[1]) ? '' : ';4' . substr($colors[$c[1]], -1)))
			. 'm' . $s . ($s === null ? '' : "\e[0m");
	}
}