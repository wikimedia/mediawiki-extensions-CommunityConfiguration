<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use MediaWiki\Extension\CommunityConfiguration\Validation\JsonSchemaValidator;

/**
 * @coversDefaultClass \MediaWiki\Extension\CommunityConfiguration\Validation\JsonSchemaValidator
 */
class JsonSchemaValidatorTest extends \MediaWikiUnitTestCase {

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
