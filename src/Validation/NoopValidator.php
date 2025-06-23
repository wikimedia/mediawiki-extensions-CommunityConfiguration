<?php

namespace MediaWiki\Extension\CommunityConfiguration\Validation;

use Iterator;
use LogicException;
use MediaWiki\Extension\CommunityConfiguration\Schema\SchemaBuilder;

/**
 * Validator that always passes
 *
 * Useful for testing purposes or for configuration providers that can work with arbitrary data.
 */
class NoopValidator implements IValidator {

	/**
	 * @inheritDoc
	 */
	public function validateStrictly( $config, ?string $version = null ): ValidationStatus {
		return ValidationStatus::newGood();
	}

	/**
	 * @inheritDoc
	 */
	public function validatePermissively( $config, ?string $version = null ): ValidationStatus {
		return ValidationStatus::newGood();
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

	/**
	 * @inheritDoc
	 * @return never
	 */
	public function getSchemaVersion(): ?string {
		throw new LogicException( __METHOD__ . ' is not supported' );
	}

	/**
	 * @inheritDoc
	 * @return never
	 */
	public function getSchemaIterator(): Iterator {
		throw new LogicException( __METHOD__ . ' is not supported' );
	}
}
