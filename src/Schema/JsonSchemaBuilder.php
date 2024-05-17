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
	public function getDefaultsMap( ?string $version = null ): stdClass {
		$res = new stdClass();
		foreach ( $this->getRootProperties( $version ) as $key => $specification ) {
			$res->{$key} = $this->getDefaultFromSpecification( $specification );
		}
		return $res;
	}
}
