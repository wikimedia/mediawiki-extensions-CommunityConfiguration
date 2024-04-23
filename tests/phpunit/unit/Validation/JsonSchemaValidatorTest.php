<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use MediaWiki\Extension\CommunityConfiguration\Schema\JsonSchema;
use MediaWiki\Extension\CommunityConfiguration\Schema\JsonSchemaBuilder;
use MediaWiki\Extension\CommunityConfiguration\Validation\JsonSchemaValidator;
use MediaWikiUnitTestCase;

/**
 * @coversDefaultClass \MediaWiki\Extension\CommunityConfiguration\Validation\JsonSchemaValidator
 * // phpcs:disable Generic.NamingConventions.UpperCaseConstantName.ClassConstantNotUpperCase
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

	public static function provideJSON(): iterable {
		yield 'OK' => [
			new class() extends JsonSchema {
				public const Number = [
					JsonSchema::TYPE => JsonSchema::TYPE_NUMBER,
				];
			},
			[ 'Number' => 1 ],
			true,
		];

		yield 'empty' => [
			new class() extends JsonSchema {
				public const Number = [
					JsonSchema::TYPE => JsonSchema::TYPE_NUMBER,
				];
			},
			[],
			true,
		];

		yield 'wrong type' => [
			new class() extends JsonSchema {
				public const Number = [
					JsonSchema::TYPE => JsonSchema::TYPE_NUMBER,
				];
			},
			[ 'Number' => 'baz' ],
			false,
		];

		yield 'additional property' => [
			new class() extends JsonSchema {
				public const Number = [
					JsonSchema::TYPE => JsonSchema::TYPE_NUMBER,
				];
			},
			[ 'Number' => 1, 'Bar' => 1 ],
			false,
		];

		yield 'required and set' => [
			new class() extends JsonSchema {
				public const Number = [
					JsonSchema::TYPE => JsonSchema::TYPE_NUMBER,
					JsonSchema::REQUIRED => true,
				];
			},
			[ 'Number' => 10 ],
			true,
		];

		yield 'required and not set' => [
			new class() extends JsonSchema {
				public const Number = [
					JsonSchema::TYPE => JsonSchema::TYPE_NUMBER,
					JsonSchema::REQUIRED => true,
				];
			},
			[],
			false,
		];

		// Doesn't really make sense, but it is good to know that `required` takes precedence if both are in fact set.
		yield 'required with default and not set' => [
			new class() extends JsonSchema {
				public const Number = [
					JsonSchema::TYPE => JsonSchema::TYPE_NUMBER,
					JsonSchema::DEFAULT => 10,
					JsonSchema::REQUIRED => true,
				];
			},
			[],
			false,
		];
	}

	/**
	 * @param JsonSchema $testSchema
	 * @param array $json
	 * @param bool $isValid expected value for both isGood and isOK of the validation
	 * @covers ::validate
	 * @dataProvider provideJSON
	 */
	public function testValidate( JsonSchema $testSchema, array $json, bool $isValid ) {
		$validator = new JsonSchemaValidator( $testSchema );

		$result = $validator->validate( (object)$json );

		$this->assertEquals( $result->isGood(), $isValid );
		$this->assertEquals( $result->isOK(), $isValid );
	}
}
