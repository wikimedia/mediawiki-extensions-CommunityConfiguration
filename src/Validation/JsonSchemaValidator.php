<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\CommunityConfiguration\Validation;

use InvalidArgumentException;
use Iterator;
use JsonSchema\Validator;
use MediaWiki\Extension\CommunityConfiguration\Schema\JsonSchema;
use MediaWiki\Extension\CommunityConfiguration\Schema\JsonSchemaBuilder;
use MediaWiki\Extension\CommunityConfiguration\Schema\JsonSchemaIterator;
use MediaWiki\Extension\CommunityConfiguration\Schema\JsonSchemaReader;
use MediaWiki\Extension\CommunityConfiguration\Schema\SchemaBuilder;
use ReflectionException;
use Wikimedia\Stats\StatsFactory;

class JsonSchemaValidator implements IValidator {

	private JsonSchemaReader $jsonSchema;
	private JsonSchemaBuilder $jsonSchemaBuilder;
	private Iterator $jsonSchemaIterator;

	/**
	 * @param JsonSchema|string $classNameOrClassInstance JsonSchema derived class name (instance only allowed in tests)
	 * @param StatsFactory $statsFactory
	 */
	public function __construct(
		string|JsonSchema $classNameOrClassInstance,
		private readonly StatsFactory $statsFactory
	) {
		// @codeCoverageIgnoreStart
		if ( is_object( $classNameOrClassInstance ) && !defined( 'MW_PHPUNIT_TEST' ) ) {
			throw new InvalidArgumentException(
				'JsonSchema should never be instantiated in production code'
			);
		}
		// @codeCoverageIgnoreEnd

		$this->jsonSchema = new JsonSchemaReader( $classNameOrClassInstance );
		$this->jsonSchemaBuilder = new JsonSchemaBuilder( $this->jsonSchema, $statsFactory );
		$this->jsonSchemaIterator = new JsonSchemaIterator( $this->jsonSchema );
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
		return $this->jsonSchemaBuilder;
	}

	/**
	 * @inheritDoc
	 */
	public function validateStrictly( $config, ?string $version = null ): ValidationStatus {
		return $this->validate( $config, false, $version );
	}

	/**
	 * @inheritDoc
	 */
	public function validatePermissively( $config, ?string $version = null ): ValidationStatus {
		return $this->validate( $config, true, $version );
	}

	/**
	 * @param mixed $config
	 * @param bool $modeForReading
	 * @param string|null $withVersion
	 *
	 * @return ValidationStatus
	 */
	private function validate( $config, bool $modeForReading, ?string $withVersion = null ): ValidationStatus {
		$timing = $this->statsFactory->withComponent( 'CommunityConfiguration' )->getTiming(
			'JsonSchemaValidator_validate_seconds'
		)->setLabel(
			'schema',
			str_replace( [ '/', '.' ], '_', $this->jsonSchema->getSchemaId() ),
		)->start();

		try {
			$rootSchema = $this->jsonSchemaBuilder->getRootSchema( $withVersion );
		} catch ( ReflectionException ) {
			return ValidationStatus::newFatal(
				'communityconfiguration-invalid-schema-version',
				$withVersion,
				$this->jsonSchemaBuilder->getSchemaName()
			);
		}
		$validator = new Validator();
		$validator->validate(
			$config,
			$rootSchema
		);
		if ( $validator->isValid() ) {
			return ValidationStatus::newGood();
		}
		$status = ValidationStatus::newGood();
		foreach ( $validator->getErrors() as $error ) {
			if ( $modeForReading && in_array( $error['constraint'], [ 'required', 'additionalProp', 'enum' ] ) ) {
				$status->addWarning(
					$error['property'],
					$error['pointer'],
					$error['message'],
					[ 'constraint' => $error['constraint'] ],
				);
			} else {
				$status->addFatal(
					$error['property'],
					$error['pointer'],
					$error['message'],
					[ 'constraint' => $error['constraint'] ],
				);
			}
		}

		$timing->stop();
		return $status;
	}

	/**
	 * @inheritDoc
	 */
	public function getSchemaVersion(): ?string {
		return $this->jsonSchema->getVersion();
	}

	public function getSchemaIterator(): Iterator {
		return $this->jsonSchemaIterator;
	}
}
