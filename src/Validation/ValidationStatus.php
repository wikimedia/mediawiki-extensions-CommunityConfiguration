<?php

namespace MediaWiki\Extension\CommunityConfiguration\Validation;

use StatusValue;

/**
 * @template T
 * @inherits StatusValue<T>
 */
class ValidationStatus extends StatusValue {
	private array $validationErrors = [];

	public function addWarning(
		string $property,
		string $pointer,
		string $messageLiteral,
		array $additionalData = []
	): void {
		$this->validationErrors[] = [
			'property' => $property,
			'pointer' => $pointer,
			'messageLiteral' => $messageLiteral,
			'additionalData' => $additionalData,
		];
		$this->warning( 'communityconfiguration-schema-validation-error', $property, $messageLiteral );
	}

	public function addFatal(
		string $property,
		string $pointer,
		string $messageLiteral,
		array $additionalData = []
	): void {
		$this->validationErrors[] = [
			'property' => $property,
			'pointer' => $pointer,
			'messageLiteral' => $messageLiteral,
			'additionalData' => $additionalData,
		];
		$this->fatal( 'communityconfiguration-schema-validation-error', $property, $messageLiteral );
	}

	/**
	 * @return array{property: string, pointer: string, messageLiteral: string}[]
	 */
	public function getValidationErrorsData(): array {
		return $this->validationErrors;
	}
}
