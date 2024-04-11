<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use EmptyIterator;
use Iterator;
use MediaWiki\Extension\CommunityConfiguration\Schema\SchemaBuilder;
use MediaWiki\Extension\CommunityConfiguration\Schema\SchemaReader;
use MediaWiki\Extension\CommunityConfiguration\Schema\SchemaVersionManager;
use MediaWiki\Extension\CommunityConfiguration\Validation\IValidator;
use MediaWiki\Extension\CommunityConfiguration\Validation\ValidationStatus;
use stdClass;

class NoopValidatorWithSchemaForTesting implements IValidator {

	/**
	 * @inheritDoc
	 */
	public function validateStrictly( $config ): ValidationStatus {
		return ValidationStatus::newGood();
	}

	/**
	 * @inheritDoc
	 */
	public function validatePermissively( $config ): ValidationStatus {
		return ValidationStatus::newGood();
	}

	/**
	 * @inheritDoc
	 */
	public function areSchemasSupported(): bool {
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function getSchemaBuilder(): SchemaBuilder {
		return new class implements SchemaBuilder {

			/**
			 * @inheritDoc
			 */
			public function getRootSchema( ?string $version = null ): array {
				return [];
			}

			/**
			 * @inheritDoc
			 */
			public function getRootProperties( ?string $version = null ): array {
				return [];
			}

			/**
			 * @inheritDoc
			 */
			public function getDefaultsMap( ?string $version = null ): stdClass {
				return (object)[];
			}

			/**
			 * @inheritDoc
			 */
			public function getSchemaName(): string {
				return '';
			}

			/**
			 * @inheritDoc
			 */
			public function getVersionManager(): SchemaVersionManager {
				throw new \LogicException();
			}

			/**
			 * @inheritDoc
			 */
			public function getSchemaReader(): SchemaReader {
				throw new \LogicException();
			}
		};
	}

	/**
	 * @inheritDoc
	 */
	public function getSchemaVersion(): ?string {
		return null;
	}

	/**
	 * @inheritDoc
	 */
	public function getSchemaIterator(): Iterator {
		return new EmptyIterator();
	}
}
