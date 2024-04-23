<?php

namespace MediaWiki\Extension\CommunityConfiguration\Schema;

use InvalidArgumentException;
use MediaWiki\Settings\Source\ReflectionSchemaSource;
use ReflectionClass;

class JsonSchemaReader {

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

	public function getReflectionSchemaSource(): ReflectionSchemaSource {
		return new ReflectionSchemaSource( $this->class->getName() );
	}

	public function isJsonSchema(): bool {
		return $this->class->isSubclassOf( JsonSchema::class );
	}

	public function assertIsJsonSchema(): void {
		if ( !$this->isJsonSchema() ) {
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
		$this->assertIsJsonSchema();
		$const = $this->class->getReflectionConstant( $constantName );
		return $const ? $const->getValue() : $default;
	}

	public function getJsonSchemaVersion(): string {
		return $this->getConstantValue( 'JSON_SCHEMA_VERSION', JsonSchema::JSON_SCHEMA_VERSION );
	}

	public function getVersion(): string {
		return $this->getConstantValue( 'VERSION', JsonSchema::VERSION );
	}

	public function getSchemaId(): string {
		return str_replace( '\\', '/', $this->class->getName() ) . '/' . $this->getVersion();
	}
}
