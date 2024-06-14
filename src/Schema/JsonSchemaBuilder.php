<?php

namespace MediaWiki\Extension\CommunityConfiguration\Schema;

use stdClass;

class JsonSchemaBuilder implements SchemaBuilder {

	private JsonSchemaReader $jsonSchema;
	private JsonSchemaVersionManager $versionManager;

	public function __construct( JsonSchemaReader $jsonSchema ) {
		$this->jsonSchema = $jsonSchema;
		$this->versionManager = new JsonSchemaVersionManager( $this->jsonSchema );
	}

	private function getJsonSchemaReader( ?string $version = null ): JsonSchemaReader {
		if ( $version === null ) {
			return $this->jsonSchema;
		}

		return $this->versionManager->getVersionForSchema(
			$version
		);
	}

	/**
	 * @inheritDoc
	 */
	public function getRootSchema( ?string $version = null ): array {
		$reader = $this->getJsonSchemaReader( $version );
		$reader->assertIsJsonSchema();

		return array_merge( [
			'$schema' => $this->jsonSchema->getJsonSchemaVersion(),
			'$id' => $this->jsonSchema->getSchemaId(),
			JsonSchema::ADDITIONAL_PROPERTIES => false,
		], $reader->getReflectionSchemaSource()->loadAsSchema( true ) );
	}

	/**
	 * @inheritDoc
	 */
	public function getRootProperties( ?string $version = null ): array {
		return $this->getRootSchema( $version )[JsonSchema::PROPERTIES];
	}

	/**
	 * Get a default from a JSON schema specification
	 *
	 * Takes into account dynamic defaults.
	 *
	 * @param array $schema
	 * @return mixed
	 */
	private function getDefaultFromSchema( array $schema ) {
		if ( isset( $schema[JsonSchema::DYNAMIC_DEFAULT] ) ) {
			$result = call_user_func( $schema[JsonSchema::DYNAMIC_DEFAULT]['callback'] );
		} else {
			$result = $schema['default'] ?? null;
		}

		if ( $schema[JsonSchema::TYPE] === JsonSchema::TYPE_OBJECT ) {
			// Convert the value to an object when TYPE_OBJECT is expected
			$result = (object)$result;
		}

		// process defaults for objects recursively
		if ( is_object( $result ) ) {
			foreach ( $schema['properties'] ?? [] as $name => $subSchema ) {
				$result->{$name} = $this->getDefaultFromSchema( $subSchema );
			}
		}

		return $result;
	}

	/**
	 * @inheritDoc
	 */
	public function getDefaultsMap( ?string $version = null ): stdClass {
		$res = new stdClass();
		foreach ( $this->getRootProperties( $version ) as $key => $specification ) {
			$res->{$key} = $this->getDefaultFromSchema( $specification );
		}
		return $res;
	}
}
