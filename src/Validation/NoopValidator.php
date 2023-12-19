<?php

namespace MediaWiki\Extension\CommunityConfiguration\Validation;

use StatusValue;

/**
 * Validator that always passes
 *
 * Useful for testing purposes or for configuration providers that can work with arbitrary data.
 */
class NoopValidator implements IValidator {

	/**
	 * @inheritDoc
	 */
	public function validate( array $config ): StatusValue {
		return StatusValue::newGood();
	}

	/**
	 * @inheritDoc
	 */
	public function getSupportedTopLevelKeys(): array {
		return [];
	}

	/**
	 * @inheritDoc
	 */
	public function getSchemaLoader(): ?SchemaLoader {
		return null;
	}
}
