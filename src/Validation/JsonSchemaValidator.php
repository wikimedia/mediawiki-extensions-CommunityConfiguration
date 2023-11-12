<?php

namespace MediaWiki\Extension\CommunityConfiguration\Validation;

use Opis\JsonSchema\{Errors\ErrorFormatter, Validator};
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

		$schema = $this->resolver->resolve( $this->schema );
		$validator->resolver()->registerRaw( $schema );

		$result = $validator->validate( (object)$config, $schema->{'$id'} );
		if ( $result->isValid() ) {
			return Status::newGood();
		}
		$status = new Status();
		$formattedError = (new ErrorFormatter())->format( $result->error() );
		$status->fatal(
			'communityconfiguration-schema-validation-error',
			array_key_first( $formattedError ),
			array_values( $formattedError )[0][0],
			// Pass the inner error with all the details
			$result->error()
		);
		// TODO process $result->subErrors if any
		return $status;
	}

	/**
	 * @inheritDoc
	 */
	public function getSupportedTopLevelKeys(): array {
		return [];
	}
}
