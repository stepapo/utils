<?php

declare(strict_types=1);

namespace Stepapo\Utils\Model;

use ArrayObject;
use InvalidArgumentException;


class Collection extends ArrayObject
{
	public function findAll(): Collection
	{
		return $this;
	}


	public function findBy(array $conds): Collection
	{
		return new Collection(array_filter(
			(array) $this,
			function (Item $item) use ($conds) {
				foreach ($conds as $property => $value) {
					if (!property_exists($item, $property)) {
						throw new InvalidArgumentException("Property '$property' does not exist.");
					}
					if (is_array($value)) {
						$has = false;
						foreach ($value as $v) {
							if ($item->$property === $v) {
								$has = true;
								break;
							}
						}
						if (!$has) {
							return false;
						}
					} else {
						if ($item->$property !== $value) {
							return false;
						}
					}
				}
				return true;
			}
		));
	}


	public function getById(mixed $id): ?Item
	{
		return $this[$id] ?? null;
	}


	public function getBy(array $conds): ?Item
	{
		return current((array) $this->findBy($conds)) ?: null;
	}
}