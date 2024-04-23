<?php

namespace MediaWiki\Extension\CommunityConfiguration\Validation;

use InvalidArgumentException;
use JsonSchema\Validator;
use MediaWiki\Extension\CommunityConfiguration\Schema\JsonSchema;
use MediaWiki\Extension\CommunityConfiguration\Schema\JsonSchemaBuilder;
use MediaWiki\Extension\CommunityConfiguration\Schema\JsonSchemaReader;
use MediaWiki\Extension\CommunityConfiguration\Schema\SchemaBuilder;
use Status;
use StatusValue;

/**
 * JSON Schema validator.
 */
class JsonSchemaValidator implements IValidator {

	private JsonSchemaReader $jsonSchema;
	private JsonSchemaBuilder $jsonSchemaBuilder;

	/**
	 * @param JsonSchema|string $classNameOrClassInstance JsonSchema derived class name (instance only allowed in tests)
	 */
	public function __construct( $classNameOrClassInstance ) {
		// @codeCoverageIgnoreStart
		if ( is_object( $classNameOrClassInstance ) ) {
			if ( !defined( 'MW_PHPUNIT_TEST' ) ) {
				throw new InvalidArgumentException(
					'JsonSchema should never be instantiated in production code'
				);
			}
			if ( !( $classNameOrClassInstance instanceof JsonSchema ) ) {
				throw new InvalidArgumentException(
					get_class( $classNameOrClassInstance ) . ' must be instance of ' . JsonSchema::class
				);
			}
		}
		// @codeCoverageIgnoreEnd

		$this->jsonSchema = new JsonSchemaReader( $classNameOrClassInstance );
		$this->jsonSchemaBuilder = new JsonSchemaBuilder( $this->jsonSchema );
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
	public function validate( $config ): StatusValue {
		$validator = new Validator();

		$validator->validate(
			$config,
			$this->jsonSchemaBuilder->getRootSchema()
		);
		if ( $validator->isValid() ) {
			return Status::newGood();
		}
		$status = new Status();
		foreach ( $validator->getErrors() as $error ) {
			$status->fatal(
				'communityconfiguration-schema-validation-error',
				$error['property'],
				$error['message'],
				// Pass the inner error with all the details
				$error
			);
		}

		return $status;
	}
}
