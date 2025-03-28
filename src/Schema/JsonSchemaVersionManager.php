<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\CommunityConfiguration\Schema;

class JsonSchemaVersionManager implements SchemaVersionManager {

	private const VERSIONS_NAMESPACE = 'Migrations';

	public function __construct( private readonly JsonSchemaReader $jsonSchema ) {
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
	 * @throws \ReflectionException if a Schema class with that version does not exist
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
