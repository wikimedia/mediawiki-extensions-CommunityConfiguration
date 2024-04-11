<?php

namespace MediaWiki\Extension\CommunityConfiguration\Schema;

class JsonSchemaVersionManager implements SchemaVersionManager {

	private const VERSIONS_NAMESPACE = 'Migrations';

	private JsonSchemaReader $jsonSchema;

	public function __construct( JsonSchemaReader $jsonSchema ) {
		$this->jsonSchema = $jsonSchema;
	}

	private function getClassPrefix(): string {
		$classNameParts = explode( '\\', $this->jsonSchema->getReflectionClass()->getName() );
		$baseName = implode(
			'\\',
			array_slice( $classNameParts, 0, count( $classNameParts ) - 1 )
		);
		$schemaName = end( $classNameParts );

		return $baseName . '\\' . self::VERSIONS_NAMESPACE . '\\' . $schemaName . '_';
	}

	/**
	 * @inheritDoc
	 * @return JsonSchemaReader
	 */
	public function getVersionForSchema( string $version ): SchemaReader {
		if ( $version == $this->jsonSchema->getVersion() ) {
			return $this->jsonSchema;
		}

		return new JsonSchemaReader(
			$this->getClassPrefix() . str_replace( '.', '_', $version )
		);
	}
}
