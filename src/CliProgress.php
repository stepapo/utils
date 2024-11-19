<?php

declare(strict_types=1);

namespace Stepapo\Utils;


class CliProgress
{
	private int $digitCount;
	private int $totalCharsCount;
	private int $current = 0;


	public function __construct(private int $itemCount)
	{
		$this->digitCount = $itemCount ? (int) floor(log($itemCount, 10) + 1) : 1;
		$this->totalCharsCount = $this->digitCount * 2 + 10;
	}


	public function start(): void
	{
		$this->iterate();
	}


	public function iterate(): void
	{
		printf(
			"\033[%dD%3d%% : %*d / %d",
			$this->totalCharsCount,
			$this->itemCount ? floor($this->current / $this->itemCount * 100) : 100,
			$this->digitCount,
			$this->current,
			$this->itemCount
		);
		$this->current++;
	}


	public function end(): void
	{
		print PHP_EOL;
	}
}
