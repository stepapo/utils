<?php

declare(strict_types=1);

namespace Stepapo\Utils\DI;

use Nette\DI\CompilerExtension;
use Nette\DI\Extensions\SearchExtension;
use Nette\DI\InvalidConfigurationException;
use Nette\PhpGenerator\ClassType;
use Nette\Schema\Processor;
use Nette\Schema\Schema;
use Nette\Schema\ValidationException;
use ReflectionClass;
use Stepapo\Utils\Command\Command;
use Webovac\Core\Factory;
use Webovac\Core\Module;


abstract class StepapoExtension extends CompilerExtension
{
	private string $moduleDir;
	private Processor $processor;
	private SearchExtension $searchExtension;


	public function __construct()
	{
		$this->moduleDir = dirname((new ReflectionClass($this))->getFileName()) . '/..';
		$this->processor = new Processor;
	}


	public function loadConfiguration(): void
	{
		$this->createSearchExtension();
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
	}


	public function afterCompile(ClassType $class): void
	{
		$this->searchExtension->afterCompile($class);
	}


	protected function getSearchConfig(): array
	{
		return [
			'module' => ['in' => $this->moduleDir, 'implements' => Module::class],
			'command' => ['in' => $this->moduleDir, 'implements' => Command::class],
			'control' => ['in' => $this->moduleDir, 'implements' => Factory::class],
			'lib' => ['in' => "$this->moduleDir/Lib/", 'classes' => '*'],
		];
	}
}