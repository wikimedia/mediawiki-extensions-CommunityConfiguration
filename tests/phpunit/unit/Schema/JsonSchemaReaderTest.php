<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use InvalidArgumentException;
use MediaWiki\Extension\CommunityConfiguration\Schema\JsonSchema;
use MediaWiki\Extension\CommunityConfiguration\Schema\JsonSchemaReader;
use MediaWiki\Settings\Source\ReflectionSchemaSource;
use MediaWikiUnitTestCase;
use ReflectionClass;

/**
 * @covers \MediaWiki\Extension\CommunityConfiguration\Schema\JsonSchemaReader
 */
class JsonSchemaReaderTest extends MediaWikiUnitTestCase {

	public static function provideIsJsonSchema() {
		return [
			'valid schema' => [ true, JsonSchemaForTesting::class ],
			'valid schema2' => [ true, JsonSchemaForTestingNewerVersion::class ],
			'test class' => [ false, self::class ],
		];
	}

	/**
	 * @param bool $expected
	 * @param string $className
	 * @dataProvider provideIsJsonSchema
	 */
	public function testIsJsonSchema( bool $expected, string $className ) {
		$this->assertSame(
			$expected,
			( new JsonSchemaReader( $className ) )->isSchema()
		);
	}

	/**
	 * @param bool $expected
	 * @param string $className
	 * @dataProvider provideIsJsonSchema
	 */
	public function testAssertIsJsonSchema( bool $expected, string $className ) {
		if ( $expected ) {
			$this->expectNotToPerformAssertions();
		} else {
			$this->expectException( InvalidArgumentException::class );
		}

		( new JsonSchemaReader( $className ) )->assertIsSchema();
	}

	public static function provideGetSchemaVersion() {
		return [
			[ '1.0.0', JsonSchemaForTesting::class ],
			[ '1.0.1', JsonSchemaForTestingNewerVersion::class ],
		];
	}

	/**
	 * @param string|null $expected
	 * @param string $className
	 * @dataProvider provideGetSchemaVersion
	 */
	public function testGetSchemaVersion( ?string $expected, string $className ) {
		$this->assertSame(
			$expected,
			( new JsonSchemaReader( $className ) )->getVersion()
		);
	}

	public static function provideGetSchemaId() {
		return [
			[
				'MediaWiki/Extension/CommunityConfiguration/Tests/JsonSchemaForTesting/1.0.0',
				JsonSchemaForTesting::class,
			],
			[
				'MediaWiki/Extension/CommunityConfiguration/Tests/JsonSchemaForTestingNewerVersion/1.0.1',
				JsonSchemaForTestingNewerVersion::class,
			],
		];
	}

	/**
	 * @param string $expected
	 * @param string $className
	 * @dataProvider provideGetSchemaId
	 */
	public function testGetSchemaId( string $expected, string $className ) {
		$this->assertSame(
			$expected,
			( new JsonSchemaReader( $className ) )->getSchemaId()
		);
	}

	public static function provideJsonSchemaClasses(): array {
		return [
			[ JsonSchemaForTesting::class ],
			[ JsonSchemaForTestingNewerVersion::class ],
		];
	}

	/**
	 * @param string $className
	 * @dataProvider provideJsonSchemaClasses
	 */
	public function testGetSchemaUri( string $className ) {
		$this->assertSame(
			JsonSchema::JSON_SCHEMA_VERSION,
			( new JsonSchemaReader( $className ) )->getJsonSchemaVersion()
		);
	}

	/**
	 * @param string $className
	 * @dataProvider provideJsonSchemaClasses
	 */
	public function testGetReflectionClass( string $className ) {
		$reflectionClass = ( new JsonSchemaReader( $className ) )->getReflectionClass();
		$this->assertInstanceOf(
			ReflectionClass::class,
			$reflectionClass
		);
		$this->assertSame(
			$className,
			$reflectionClass->getName()
		);
	}

	/**
	 * @param string $className
	 * @dataProvider provideJsonSchemaClasses
	 */
	public function testGetReflectionSchemaSource( string $className ) {
		$this->assertInstanceOf(
			ReflectionSchemaSource::class,
			( new JsonSchemaReader( $className ) )->getReflectionSchemaSource()
		);
	}
}
