<?php

namespace Stepapo\Utils\Orm;

use App\Lib\OrmFunctions;
use Nette\InvalidArgumentException;
use Nette\Utils\Strings;
use Nextras\Dbal\IConnection;
use Nextras\Dbal\Platforms\IPlatform;
use Nextras\Dbal\QueryBuilder\QueryBuilder;
use Nextras\Orm\Collection\Aggregations\Aggregator;
use Nextras\Orm\Collection\Functions\CollectionFunction;
use Nextras\Orm\Collection\Functions\Result\ArrayExpressionResult;
use Nextras\Orm\Collection\Functions\Result\DbalExpressionResult;
use Nextras\Orm\Collection\Helpers\ArrayCollectionHelper;
use Nextras\Orm\Collection\Helpers\DbalQueryBuilderHelper;
use Nextras\Orm\Entity\IEntity;


trait StepapoOrmFunctions
{
	public const string LIKE_FILTER = 'likeFilter';
	public const string CONCAT_FILTER = 'concatFilter';
	public const string CONCAT_SORT = 'concatSort';
	public const string COALESCE_SORT = 'coalesceSort';
	public const string YEAR_FILTER = 'yearFilter';
	public const string MONTH_FILTER = 'monthFilter';
	public const string DAY_FILTER = 'dayFilter';
	public const string DATE_FILTER = 'dateFilter';


	public function __construct(
		private IConnection $connection,
	) {}


	public function call(string $name): CollectionFunction
	{
		return new class($name, $this->connection) implements CollectionFunction {
			public function __construct(private string $name, private IConnection $connection)
			{}

			public function processDbalExpression(
				DbalQueryBuilderHelper $helper,
				QueryBuilder $builder,
				array $args,
				?Aggregator $aggregator = null,
			): DbalExpressionResult
			{
				return OrmFunctions::{$this->name}($this->connection->getPlatform(), $helper, $builder, $args, $aggregator);
			}

			public function processArrayExpression(
				ArrayCollectionHelper $helper,
				IEntity $entity,
				array $args,
				?Aggregator $aggregator = null,
			): ArrayExpressionResult
			{
				return new ArrayExpressionResult(null);
			}
		};
	}


	public static function likeFilter(IPlatform $platform, DbalQueryBuilderHelper $helper, QueryBuilder $builder, array $args, ?Aggregator $aggregator): DbalExpressionResult
	{
		assert(count($args) === 2 && (is_string($args[0]) || (is_array($args[0]) && count($args[0]) > 0)) && is_string($args[1]));
		$parts = [];
		$values = [];
		$columns = [];
		foreach ((array) $args[0] as $col) {
			$parts[] = 'LOWER(%column) LIKE %_like_';
			$column = $helper->processExpression($builder, $col, $aggregator);
			$values[] = $column->args[0];
			$columns[] = $column;
			$values[] = Strings::lower($args[1]);
		}
		$expression = implode(' OR ', $parts);
		return static::createDbalExpression($expression, $values, $columns, $aggregator);
	}


	public static function concatFilter(IPlatform $platform, DbalQueryBuilderHelper $helper, QueryBuilder $builder, array $args, ?Aggregator $aggregator): DbalExpressionResult
	{
		assert(count($args) === 2 && is_array($args[0]) && count($args[0]) > 0 && is_string($args[1]));
		$combinations = is_array($args[0][0]) ? $args[0] : [$args[0]];
		$parts = [];
		$values = [];
		$columns = [];
		foreach ($combinations as $cols) {
			$placeholders = [];
			$part = $platform->getName() === 'pgsql' ? 'LOWER(' : 'LOWER(CONCAT(';
			if (!is_array($cols)) {
				throw new InvalidArgumentException;
			}
			foreach ($cols as $col) {
				$placeholders[] = '%column';
				$column = $helper->processExpression($builder, $col, $aggregator);
				$values[] = $column->args[0];
				$columns[] = $column;
			}
			$values[] = Strings::lower($args[1]);
			$part .= implode($platform->getName() === 'pgsql' ? " || ' ' || " : ", ' ' , ", $placeholders) . ($platform->getName() === 'pgsql' ? ')' : '))') . ' LIKE %_like_';
			$parts[] = $part;
		}
		$expression = implode(' OR ', $parts);
		return static::createDbalExpression($expression, $values, $columns, $aggregator);
	}


	public static function concatSort(IPlatform $platform, DbalQueryBuilderHelper $helper, QueryBuilder $builder, array $args, ?Aggregator $aggregator): DbalExpressionResult
	{
		assert(is_array($args) && count($args) > 0);
		$expression = $platform->getName() === 'pgsql' ? '' : 'CONCAT(';
		$placeholders = [];
		$values = [];
		$columns = [];
		foreach ($args as $col) {
			$placeholders[] = '%column';
			$column = $helper->processExpression($builder, $col, $aggregator);
			$values[] = $column->args[0];
			$columns[] = $column;
		}
		$expression .= implode($platform->getName() === 'pgsql' ? ' || ' : ', ', $placeholders) . ($platform->getName() === 'pgsql' ? '' : ')');
		return static::createDbalExpression($expression, $values, $columns, $aggregator);
	}


	public static function coalesceSort(IPlatform $platform, DbalQueryBuilderHelper $helper, QueryBuilder $builder, array $args, ?Aggregator $aggregator): DbalExpressionResult
	{
		assert(count($args) === 1 && is_array($args[0]) && count($args[0]) > 0);
		$expression = 'COALESCE(';
		$placeholders = [];
		$values = [];
		$columns = [];
		foreach ($args[0] as $col) {
			$placeholders[] = '%column';
			$column = $helper->processExpression($builder, $col, $aggregator);
			$values[] = $column->args[0];
			$columns[] = $column;
		}
		$expression .= implode(', ', $placeholders) . ')';
		return static::createDbalExpression($expression, $values, $columns, $aggregator);
	}


	public static function yearFilter(IPlatform $platform, DbalQueryBuilderHelper $helper, QueryBuilder $builder, array $args, ?Aggregator $aggregator): DbalExpressionResult
	{
		assert(count($args) === 2 && is_string($args[0]) && is_string($args[1]));
		$column = $helper->processExpression($builder, $args[0], $aggregator);
		return static::createDbalExpression(
			$platform->getName() === 'pgsql' ? 'EXTRACT(year FROM %column) = %i' : 'YEAR(%column) = %i',
			[$column->args[0], $args[1]],
			[$column],
			$aggregator,
		);
	}


	public static function monthFilter(IPlatform $platform, DbalQueryBuilderHelper $helper, QueryBuilder $builder, array $args, ?Aggregator $aggregator): DbalExpressionResult
	{
		assert(count($args) === 2 && is_string($args[0]) && is_string($args[1]));
		$column = $helper->processExpression($builder, $args[0], $aggregator);
		return static::createDbalExpression(
			$platform->getName() === 'pgsql' ? 'EXTRACT(month FROM %column) = %i' : 'MONTH(%column) = %i',
			[$column->args[0], $args[1]],
			[$column],
			$aggregator,
		);
	}


	public static function dayFilter(IPlatform $platform, DbalQueryBuilderHelper $helper, QueryBuilder $builder, array $args, ?Aggregator $aggregator): DbalExpressionResult
	{
		assert(count($args) === 2 && is_string($args[0]) && is_string($args[1]));
		$column = $helper->processExpression($builder, $args[0], $aggregator);
		return static::createDbalExpression(
			$platform->getName() === 'pgsql' ? 'EXTRACT(day FROM %column) = %i' : 'DAY(%column) = %i',
			[$column->args[0], $args[1]],
			[$column],
			$aggregator,
		);
	}


	public static function dateFilter(IPlatform $platform, DbalQueryBuilderHelper $helper, QueryBuilder $builder, array $args, ?Aggregator $aggregator): DbalExpressionResult
	{
		assert(count($args) === 2 && is_string($args[0]) && is_string($args[1]));
		$column = $helper->processExpression($builder, $args[0], $aggregator);
		return static::createDbalExpression(
			$platform->getName() === 'pgsql' ? '$column::date = %dt::date' : 'DATE(%column) = DATE(%dt)',
			[$column->args[0], $args[1]],
			[$column],
			$aggregator,
		);
	}


	private static function createDbalExpression(string $expression, array $args, array $columns = [], ?Aggregator $aggregator = null)
	{
		return new DbalExpressionResult(
			expression: $expression,
			args: $args,
			joins: array_merge(...array_map(fn(DbalExpressionResult $result) => $result->joins, $columns)),
			groupBy: array_merge(...array_map(fn(DbalExpressionResult $result) => $result->groupBy, $columns)),
			aggregator: $aggregator,
		);
	}
}