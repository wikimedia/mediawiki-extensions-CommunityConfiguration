<?php

namespace MediaWiki\Extension\CommunityConfiguration\Validation;

use MediaWiki\Extension\CommunityConfiguration\Schema\SchemaBuilder;
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
	public function validate( $config ): StatusValue {
		return StatusValue::newGood();
	}

	/**
	 * @inheritDoc
	 */
	public function getSchemaBuilder(): ?SchemaBuilder {
		return null;
	}
}
