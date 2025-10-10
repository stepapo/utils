<?php

declare(strict_types=1);

namespace Stepapo\Utils\DI;

use Nette\DI\CompilerExtension;
use Nette\DI\Extensions\DecoratorExtension;
use Nette\DI\Extensions\SearchExtension;
use Nette\DI\InvalidConfigurationException;
use Nette\PhpGenerator\ClassType;
use Nette\Schema\Processor;
use Nette\Schema\Schema;
use Nette\Schema\ValidationException;
use ReflectionClass;
use Stepapo\Utils\Injectable;
use Stepapo\Utils\Service;


abstract class StepapoExtension extends CompilerExtension
{
	private string $moduleDir;
	private Processor $processor;
	private SearchExtension $searchExtension;
	private DecoratorExtension $decoratorExtension;


	public function __construct()
	{
		$this->moduleDir = dirname((new ReflectionClass($this))->getFileName()) . '/..';
		$this->processor = new Processor;
	}


	public function loadConfiguration(): void
	{
		$this->createSearchExtension();
		$this->createDecoratorExtension();
		$this->compiler->loadDefinitionsFromConfig(
			(array) $this->loadFromFile("$this->moduleDir/DI/config.neon")['services'],
		);
	}


	protected function createSearchExtension(): void
	{
		$rootDir = $this->getContainerBuilder()->parameters['rootDir'];
		$this->searchExtension = new SearchExtension("$rootDir/temp/cache/$this->name.search");
		$this->searchExtension->setCompiler($this->compiler, $this->prefix('search'));
		$config = $this->processSchema($this->searchExtension->getConfigSchema(), $this->getSearchConfig());
		$this->searchExtension->setConfig($config);
		$this->searchExtension->loadConfiguration();
	}


	protected function createDecoratorExtension(): void
	{
		$this->decoratorExtension = new DecoratorExtension;
		$this->decoratorExtension->setCompiler($this->compiler, $this->prefix('decorator'));
		$config = $this->processSchema($this->decoratorExtension->getConfigSchema(), $this->getDecoratorConfig());
		$this->decoratorExtension->setConfig($config);
		$this->decoratorExtension->loadConfiguration();
	}


	protected function processSchema(Schema $schema, array $config)
	{
		try {
			return $this->processor->process($schema, $config);
		} catch (ValidationException $e) {
			throw new InvalidConfigurationException($e->getMessage());
		}
	}


	public function beforeCompile(): void
	{
		$this->searchExtension->beforeCompile();
		$this->decoratorExtension->beforeCompile();
	}


	public function afterCompile(ClassType $class): void
	{
		$this->searchExtension->afterCompile($class);
		$this->decoratorExtension->afterCompile($class);
	}


	protected function getSearchConfig(): array
	{
		return [
			['in' => $this->moduleDir, 'implements' => Service::class],
		];
	}


	private function getDecoratorConfig(): array
	{
		return [
			Injectable::class => ['inject' => true],
		];
	}
}