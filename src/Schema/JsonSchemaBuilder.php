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
	 * @inheritDoc
	 */
	public function getDefaultsMap(): stdClass {
		$res = new stdClass();
		foreach ( $this->getRootProperties() as $key => $specification ) {
			$res->{$key} = $specification['default'] ?? null;
		}
		return $res;
	}
}
