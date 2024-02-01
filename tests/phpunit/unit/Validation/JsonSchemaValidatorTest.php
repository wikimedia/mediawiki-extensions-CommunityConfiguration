<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use MediaWiki\Extension\CommunityConfiguration\Validation\JsonSchemaValidator;

/**
 * @coversDefaultClass \MediaWiki\Extension\CommunityConfiguration\Validation\JsonSchemaValidator
 */
class JsonSchemaValidatorTest extends \MediaWikiUnitTestCase {

	public static function provideJSON(): array {
		return [
			[ [ 'foo' => 1 ], true ],
			[ [ 'foo' => 'baz' ], false ],
			[ [ 'foo' => 1, 'bar' => 1 ], false ],
			[ [ 'bar' => 1 ], false ],
		];
	}

	/**
	 * @param array|null $json
	 * @covers ::validate
	 * @dataProvider provideJSON
	 */
	public function testValidate( array $json, bool $isValid ) {
		$validator = new JsonSchemaValidator( __DIR__ . '/schema_draft-07.json' );
		$result = $validator->validate( $json );
		$this->assertEquals( $result->isGood(), $isValid );
	}
}
