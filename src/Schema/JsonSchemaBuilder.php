<?php

namespace MediaWiki\Extension\CommunityConfiguration\Schema;

use stdClass;
use Wikimedia\Stats\IBufferingStatsdDataFactory;

class JsonSchemaBuilder implements SchemaBuilder {

	private IBufferingStatsdDataFactory $statsdDataFactory;
	private JsonSchemaReader $jsonSchema;
	private JsonSchemaVersionManager $versionManager;

	public function __construct(
		IBufferingStatsdDataFactory $statsdDataFactory,
		JsonSchemaReader $jsonSchema
	) {
		$this->statsdDataFactory = $statsdDataFactory;
		$this->jsonSchema = $jsonSchema;
		$this->versionManager = new JsonSchemaVersionManager( $this->jsonSchema );
	}

	/**
	 * Get schema reader for given version
	 *
	 * @param string|null $version
	 * @return JsonSchemaReader
	 */
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
	public function getSchemaName(): string {
		return $this->getJsonSchemaReader()->getSchemaId();
	}

	/**
	 * @inheritDoc
	 */
	public function getVersionManager(): SchemaVersionManager {
		return $this->versionManager;
	}

	/**
	 * @inheritDoc
	 */
	public function getSchemaReader(): SchemaReader {
		return $this->jsonSchema;
	}

	/**
	 * @inheritDoc
	 */
	public function getRootSchema( ?string $version = null ): array {
		$start = microtime( true );
		$reader = $this->getJsonSchemaReader( $version );
		$reader->assertIsSchema();

		$result = array_merge( [
			'$schema' => $this->jsonSchema->getJsonSchemaVersion(),
			'$id' => $this->jsonSchema->getSchemaId(),
			JsonSchema::ADDITIONAL_PROPERTIES => false,
			'required' => $reader->getRequiredTopLevelProperties(),
		], $reader->getReflectionSchemaSource()->loadAsSchema( true ) );
		$this->statsdDataFactory->timing(
			'timing.communityConfiguration.JsonSchemaBuilder.getRootSchema',
			microtime( true ) - $start
		);
		return $result;
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
	 * @param bool $useDynamicDefaults Evaluate dynamic defaults
	 * @return mixed
	 */
	private function getDefaultFromSchema( array $schema, bool $useDynamicDefaults ) {
		if ( $useDynamicDefaults && isset( $schema[JsonSchema::DYNAMIC_DEFAULT] ) ) {
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
				$result->{$name} = $this->getDefaultFromSchema( $subSchema, $useDynamicDefaults );
			}
		}

		return $result;
	}

	/**
	 * @inheritDoc
	 */
	public function getDefaultsMap(
		?string $version = null,
		bool $useDynamicDefaults = true
	): stdClass {
		$start = microtime( true );
		$res = new stdClass();
		foreach ( $this->getRootProperties( $version ) as $key => $specification ) {
			$res->{$key} = $this->getDefaultFromSchema( $specification, $useDynamicDefaults );
		}
		$this->statsdDataFactory->timing(
			'timing.communityConfiguration.JsonSchemaBuilder.getDefaultsMap',
			microtime( true ) - $start
		);
		return $res;
	}
}
