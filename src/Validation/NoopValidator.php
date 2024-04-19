<?php

namespace MediaWiki\Extension\CommunityConfiguration\Validation;

use LogicException;
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
	public function validateStrictly( $config ): StatusValue {
		return StatusValue::newGood();
	}

	/**
	 * @inheritDoc
	 */
	public function validatePermissively( $config ): StatusValue {
		return StatusValue::newGood();
	}

	/**
	 * @inheritDoc
	 */
	public function areSchemasSupported(): bool {
		return false;
	}

	/**
	 * @inheritDoc
	 * @return never
	 */
	public function getSchemaBuilder(): SchemaBuilder {
		throw new LogicException( __METHOD__ . ' is not supported' );
	}
}
