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
			function (Item $entity) use ($conds) {
				foreach ($conds as $property => $value) {
					if (!property_exists($entity, $property)) {
						throw new InvalidArgumentException;
					}
					if ($entity->$property !== $value) {
						return false;
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