<?php

namespace Stepapo\Utils;

use App\Model\File\FileData;
use Nette\Forms\Container;
use Nette\Forms\Form;


class FileControl extends Container
{
	private bool $required = false;


	public function __construct(
		string|\Stringable|null $label = null,
		FileData|null $fileData = null,
	) {
		$this->setMappedType(FileData::class);
		$this->addHidden('identifier', $fileData?->identifier);
		$this->addUpload('upload', $label)
			->setNullable();
		if ($fileData) {
			$this['upload']->setOption('file', $fileData);
		}
	}


	public function getValues(string|object|bool|null $returnType = null, ?array $controls = null): object|array
	{
		return parent::getValue();
	}


	/** @param FileData|null $values */
	public function setValues(array|object $values, bool $erase = false, bool $onlyDisabled = false): static
	{
		if ($values) {
			$this['identifier']->setDefaultValue($values->identifier);
			$this['upload']->setOption('file', $values);
		}
		return $this;
	}


	public function setRequired(bool|string $required = true): static
	{
		if ($required) {
			$this->required = true;
			$this['upload']
				->addConditionOn($this['identifier'], Form::Blank)
				->setRequired($required);
		}
		return $this;
	}


	public function isRequired(): bool
	{
		return $this->required;
	}
}