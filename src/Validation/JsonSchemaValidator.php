<?php

namespace MediaWiki\Extension\CommunityConfiguration\Validation;

use JsonSchema\Validator;
use Status;
use StatusValue;

/**
 * JSON Schema validator.
 */
class JsonSchemaValidator implements IValidator {

	private SchemaLoader $loader;

	/**
	 * @param string $schema
	 */
	public function __construct( string $schema ) {
		$this->loader = new SchemaLoader( $schema );
	}

	public function getSchemaLoader(): SchemaLoader {
		return $this->loader;
	}

	private function arrayToStdClass( array $config ): \stdClass {
		$res = new \stdClass();
		foreach ( $config as $key => $value ) {
			if ( is_array( $value ) ) {
				$value = $this->arrayToStdClass( $value );
			}
			$res->$key = $value;
		}
		return $res;
	}

	/**
	 * @inheritDoc
	 */
	public function validate( array $config ): StatusValue {
		$validator = new Validator();

		// REVIEW Using type array for $config prevents from validating
		// other valid json data types, eg: string, array. Consider
		// using a mixed type for config objects or restrict the
		// root type of configuration schemas to "object".
		$data = $this->arrayToStdClass( $config );
		$validator->validate(
			$data,
			(object)[ '$ref' => 'file://' . $this->loader->getPath() ]
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

	/**
	 * @inheritDoc
	 */
	public function getSupportedTopLevelKeys(): array {
		return [];
	}
}
