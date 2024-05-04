<?php

namespace MediaWiki\Extension\CommunityConfiguration\Schema;

use stdClass;

class JsonSchemaBuilder implements SchemaBuilder {

	private JsonSchemaReader $jsonSchema;

	public function __construct( JsonSchemaReader $jsonSchema ) {
		$this->jsonSchema = $jsonSchema;
	}

	/**
	 * @inheritDoc
	 */
	public function getRootSchema(): array {
		$this->jsonSchema->assertIsJsonSchema();

		return array_merge( [
			'$schema' => $this->jsonSchema->getJsonSchemaVersion(),
			'$id' => $this->jsonSchema->getSchemaId(),
			JsonSchema::ADDITIONAL_PROPERTIES => false,
		], $this->jsonSchema->getReflectionSchemaSource()->loadAsSchema( true ) );
	}

	/**
	 * @inheritDoc
	 */
	public function getRootProperties(): array {
		return $this->getRootSchema()[JsonSchema::PROPERTIES];
	}

	/**
	 * Get a default from a JSON schema specification
	 *
	 * Takes into account dynamic defaults.
	 *
	 * @param array $specification
	 * @return mixed
	 */
	private function getDefaultFromSpecification( array $specification ) {
		if ( isset( $specification[JsonSchema::DYNAMIC_DEFAULT] ) ) {
			return call_user_func( $specification[JsonSchema::DYNAMIC_DEFAULT]['callback'] );
		}
		return $specification['default'] ?? null;
	}

	/**
	 * @inheritDoc
	 */
	public function getDefaultsMap(): stdClass {
		$res = new stdClass();
		foreach ( $this->getRootProperties() as $key => $specification ) {
			$res->{$key} = $this->getDefaultFromSpecification( $specification );
		}
		return $res;
	}
}
