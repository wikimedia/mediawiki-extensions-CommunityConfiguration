<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use MediaWiki\Extension\CommunityConfiguration\Schema\JsonSchemaBuilder;
use MediaWiki\Extension\CommunityConfiguration\Validation\JsonSchemaValidator;
use MediaWikiUnitTestCase;

/**
 * @coversDefaultClass \MediaWiki\Extension\CommunityConfiguration\Validation\JsonSchemaValidator
 */
class JsonSchemaValidatorTest extends MediaWikiUnitTestCase {

	/**
	 * @covers ::__construct
	 */
	public function testConstruct() {
		$this->assertInstanceOf(
			JsonSchemaValidator::class,
			new JsonSchemaValidator( JsonSchemaForTesting::class )
		);
	}

	/**
	 * @covers ::getSchemaBuilder
	 */
	public function testGetSchemaBuilder() {
		$this->assertInstanceOf(
			JsonSchemaBuilder::class,
			( new JsonSchemaValidator( JsonSchemaForTesting::class ) )->getSchemaBuilder()
		);
	}

	public static function provideJSON(): array {
		return [
			'OK' => [ [ 'Foo' => 1 ], true ],
			'wrong type' => [ [ 'Foo' => 'baz' ], false ],
			'additional property' => [ [ 'Foo' => 1, 'Bar' => 1 ], false ],
		];
	}

	/**
	 * @param array|null $json
	 * @covers ::validate
	 * @dataProvider provideJSON
	 */
	public function testValidate( array $json, bool $isValid ) {
		$validator = new JsonSchemaValidator( JsonSchemaForTesting::class );
		$result = $validator->validate( (object)$json );
		$this->assertEquals( $result->isGood(), $isValid );
	}
}
