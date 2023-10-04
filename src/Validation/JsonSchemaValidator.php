<?php

namespace MediaWiki\Extension\CommunityConfiguration\Validation;

use JsonSchema\Validator;
use Status;
use StatusValue;

/**
 * JSON Schema validator.
 */
class JsonSchemaValidator implements IValidator {

	// REVIEW name it path, create some abstraction?
	private string $schema;
	private SchemaResolver $resolver;

	/**
	 * @param string $schema
	 */
	public function __construct( string $schema ) {
		$this->resolver = new SchemaResolver();
		$this->schema = $schema;
	}

	/**
	 * @inheritDoc
	 */
	public function validate( array $config ): StatusValue {
		$validator = new Validator();
		$schemaPath = $this->resolver->resolvePath( $this->schema );

		// REVIEW Using type array for $config prevents from validating
		// other valid json data types, eg: string, array. Consider
		// using a mixed type for config objects or restrict the
		// root type of configuration schemas to "object".
		$data = (object)$config;
		$validator->validate( $data, (object)['$ref' => 'file://' . $schemaPath] );
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

	/**
	 * @inheritDoc
	 */
	public function getSupportedTopLevelKeys(): array {
		return [];
	}
}
