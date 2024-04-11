<?php

namespace MediaWiki\Extension\CommunityConfiguration\Validation;

use JsonSchema\Validator;
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
	 * @param string $schemaClassName
	 */
	public function __construct( string $schemaClassName ) {
		$this->jsonSchema = new JsonSchemaReader( $schemaClassName );
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
