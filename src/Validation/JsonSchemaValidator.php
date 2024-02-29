<?php

namespace MediaWiki\Extension\CommunityConfiguration\Validation;

use InvalidArgumentException;
use JsonSchema\Validator;
use MediaWiki\Extension\CommunityConfiguration\Schema\JsonSchemaBuilder;
use MediaWiki\Extension\CommunityConfiguration\Schema\SchemaBuilder;
use Status;
use StatusValue;
use stdClass;

/**
 * JSON Schema validator.
 */
class JsonSchemaValidator implements IValidator {

	private JsonSchemaBuilder $jsonSchemaBuilder;

	/**
	 * @param string $schemaClassName
	 */
	public function __construct( string $schemaClassName ) {
		$this->jsonSchemaBuilder = new JsonSchemaBuilder( $schemaClassName );
	}

	/**
	 * @inheritDoc
	 */
	public function getSchemaBuilder(): ?SchemaBuilder {
		return $this->jsonSchemaBuilder;
	}

	/**
	 * Is an array associative?
	 *
	 * For the purpose of this helper function, any array with non-numeric keys is considered to
	 * be an associative array.
	 *
	 * @param array $array
	 * @return bool
	 */
	private function isArrayAssociative( array $array ): bool {
		// PHP documentation requires all array keys to be either integers or strings. This means
		// there can be no key that is not a string and also not a number. Hence, if there is a
		// non-zero number of string keys, the array must be associative (as defined in the
		// documenting comment).
		return count( array_filter( array_keys( $array ), 'is_string' ) ) > 0;
	}

	/**
	 * Recursively convert an associative array into a std class
	 *
	 * @param array $config
	 * @return stdClass
	 * @throws InvalidArgumentException when $config is not an associative array
	 */
	private function arrayToStdClass( array $config ): stdClass {
		if ( !$this->isArrayAssociative( $config ) ) {
			throw new InvalidArgumentException(
				__METHOD__ . ' can only process associative arrays.'
			);
		}

		$res = new stdClass();
		foreach ( $config as $key => $value ) {
			if ( is_array( $value ) && $this->isArrayAssociative( $value ) ) {
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
			$this->arrayToStdClass( $this->jsonSchemaBuilder->getRootSchema() )
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
