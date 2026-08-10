<?php

namespace MediaWiki\Extension\CommunityConfiguration\Schema;

use InvalidArgumentException;
use MediaWiki\Settings\Source\ReflectionSchemaSource;
use ReflectionClass;

class JsonSchemaReader implements SchemaReader {

	private ReflectionClass $class;

	/**
	 * @param JsonSchema|string $classNameOrClassInstance JsonSchema derived class name (instance only allowed in tests)
	 */
	public function __construct( $classNameOrClassInstance ) {
		// @codeCoverageIgnoreStart
		if ( is_object( $classNameOrClassInstance ) ) {
			if ( !defined( 'MW_PHPUNIT_TEST' ) ) {
				throw new InvalidArgumentException(
					'JsonSchema should never be instantiated in production code'
				);
			}
			if ( !( $classNameOrClassInstance instanceof JsonSchema ) ) {
				throw new InvalidArgumentException(
					get_class( $classNameOrClassInstance ) . ' must be instance of ' . JsonSchema::class
				);
			}
		}
		// @codeCoverageIgnoreEnd

		$this->class = new ReflectionClass( $classNameOrClassInstance );
	}

	public function getReflectionClass(): ReflectionClass {
		return $this->class;
	}

	public function getReflectionSchemaSource(): ReflectionSchemaSource {
		return new ReflectionSchemaSource( $this->class->getName() );
	}

	public function isSchema(): bool {
		return $this->class->isSubclassOf( JsonSchema::class );
	}

	public function assertIsSchema(): void {
		if ( !$this->isSchema() ) {
			throw new InvalidArgumentException(
				__CLASS__ . ' needs to be used with a class that implements '
				. JsonSchema::class . '.'
			);
		}
	}

	/**
	 * @param string $constantName
	 * @param mixed $default
	 * @return mixed
	 */
	private function getConstantValue( string $constantName, $default ) {
		$this->assertIsSchema();
		$const = $this->class->getReflectionConstant( $constantName );
		return $const ? $const->getValue() : $default;
	}

	public function getRequiredTopLevelProperties(): array {
		return $this->getConstantValue( '__REQUIRED', [] );
	}

	public function getJsonSchemaVersion(): string {
		return $this->getConstantValue( 'JSON_SCHEMA_VERSION', JsonSchema::JSON_SCHEMA_VERSION );
	}

	public function getVersion(): ?string {
		return $this->getConstantValue( 'VERSION', JsonSchema::VERSION );
	}

	public function getNextVersion(): ?string {
		return $this->getConstantValue( 'SCHEMA_NEXT_VERSION', JsonSchema::SCHEMA_NEXT_VERSION );
	}

	public function getPreviousVersion(): ?string {
		return $this->getConstantValue( 'SCHEMA_PREVIOUS_VERSION', JsonSchema::SCHEMA_PREVIOUS_VERSION );
	}

	public function getSchemaId(): string {
		$schemaId = str_replace( '\\', '/', $this->class->getName() );
		if ( $this->getVersion() ) {
			$schemaId .= '/' . $this->getVersion();
		}
		return $schemaId;
	}

	public function getSchemaConverterId(): ?string {
		return $this->getConstantValue( 'SCHEMA_CONVERTER', JsonSchema::SCHEMA_CONVERTER );
	}

	/**
	 * @inheritDoc
	 */
	public function getUiSchema(): ?array {
		$uiSchemaClass = $this->getConstantValue( 'UI_SCHEMA', JsonSchema::UI_SCHEMA );
		if ( $uiSchemaClass === null ) {
			return null;
		}
		// A bad UI_SCHEMA is a coding error, not a runtime condition. Fail early and loudly,
		// so that SchemaProviderTestCase catches it before the schema reaches a wiki.
		if ( !is_string( $uiSchemaClass ) || !is_subclass_of( $uiSchemaClass, UISchema::class ) ) {
			throw new InvalidArgumentException(
				$this->class->getName() . '::UI_SCHEMA must name a subclass of ' . UISchema::class . '.'
			);
		}
		// An empty layout has the same effect as no UI schema at all.
		return $uiSchemaClass::ROOT ?: null;
	}
}
