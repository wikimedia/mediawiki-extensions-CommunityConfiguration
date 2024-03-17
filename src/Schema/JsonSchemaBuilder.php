<?php

namespace MediaWiki\Extension\CommunityConfiguration\Schema;

use InvalidArgumentException;
use MediaWiki\Settings\Source\ReflectionSchemaSource;
use MediaWiki\Settings\Source\SettingsSource;
use ReflectionClass;
use stdClass;

class JsonSchemaBuilder implements SchemaBuilder {

	private string $className;

	public function __construct( string $className ) {
		$this->className = $className;
	}

	private function getSettingsSource(): SettingsSource {
		return new ReflectionSchemaSource( $this->className );
	}

	/**
	 * @inheritDoc
	 */
	public function getRootSchema(): array {
		$reflectionClass = new ReflectionClass( $this->className );
		if ( !$reflectionClass->isSubclassOf( JsonSchema::class ) ) {
			throw new InvalidArgumentException(
				__CLASS__ . ' needs to be used with a class that implements '
					. JsonSchema::class . '.'
			);
		}

		$schemaUriConstant = $reflectionClass->getReflectionConstant( 'SCHEMA_URI' );
		$schemaVersionConstant = $reflectionClass->getReflectionConstant( 'VERSION' );
		return [
			'$schema' => $schemaUriConstant ? $schemaUriConstant->getValue() : JsonSchema::SCHEMA_URI,
			'$id' => str_replace( '\\', '/', $this->className ) . '/'
				. ( $schemaVersionConstant ? $schemaVersionConstant->getValue() : JsonSchema::VERSION ),
			JsonSchema::TYPE => JsonSchema::TYPE_OBJECT,
			JsonSchema::PROPERTIES => $this->getSettingsSource()->load()['config-schema'] ?? [],
			JsonSchema::ADDITIONAL_PROPERTIES => false,
		];
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
