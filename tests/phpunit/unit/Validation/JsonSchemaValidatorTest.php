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
			[],
			true,
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
			[],
			true,
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
			[ 'type' ],
			false,
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
			[ 'additionalProp' ],
			true,
			false,
		];

		yield 'object type OK' => [
			new class() extends JsonSchema {
				public const ProposedIntroLinks = [
					JsonSchema::TYPE => JsonSchema::TYPE_OBJECT,
					JsonSchema::PROPERTIES => [
						'create' => [
							JsonSchema::TYPE => JsonSchema::TYPE_STRING,
						],
						'image' => [
							JsonSchema::TYPE => JsonSchema::TYPE_STRING,
						],
					],
					JsonSchema::ADDITIONAL_PROPERTIES => false,
				];
			},
			[ 'ProposedIntroLinks' => (object)[ 'create' => 'foo', 'image' => 'bar' ] ],
			true,
			[],
			true,
			true,
		];

		yield 'additional property in object' => [
			new class() extends JsonSchema {
				public const ProposedIntroLinks = [
					JsonSchema::TYPE => JsonSchema::TYPE_OBJECT,
					JsonSchema::PROPERTIES => [
						'create' => [
							JsonSchema::TYPE => JsonSchema::TYPE_STRING,
						],
						'image' => [
							JsonSchema::TYPE => JsonSchema::TYPE_STRING,
						],
					],
					JsonSchema::ADDITIONAL_PROPERTIES => false,
				];
			},
			[ 'ProposedIntroLinks' => (object)[ 'create' => 'foo', 'image' => 'bar', 'Extra' => 7 ] ],
			false,
			[ 'additionalProp' ],
			true,
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
			[],
			true,
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
			[ 'required' ],
			true,
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
			[ 'required' ],
			true,
			false,
		];

		yield 'enum with correct value' => [
			new class() extends JsonSchema {
				public const OneOfThese = [
					JsonSchema::TYPE => JsonSchema::TYPE_STRING,
					JsonSchema::ENUM => [ 'foo', 'bar' ],
				];
			},
			[ 'OneOfThese' => 'foo' ],
			true,
			[],
			true,
			true,
		];

		yield 'enum with incorrect value' => [
			new class() extends JsonSchema {
				public const OneOfThese = [
					JsonSchema::TYPE => JsonSchema::TYPE_STRING,
					JsonSchema::ENUM => [ 'foo', 'bar' ],
				];
			},
			[ 'OneOfThese' => 'not in list' ],
			false,
			[ 'enum' ],
			true,
			false,
		];
	}

	/**
	 * @param JsonSchema $testSchema
	 * @param array $json
	 * @param bool $expectedIsStrictlyValid expected value for both isGood and isOK of the validation
	 * @param array $expectedMessageConstraints the constraints provided by the library for why validation fails
	 * @param bool $expectedIsPermissivelyOk
	 * @param bool $expectedIsPermissivelyGood
	 * @covers ::validate
	 * @covers ::validateStrictly
	 * @covers ::validatePermissively
	 * @dataProvider provideJSON
	 */
	public function testValidate(
		JsonSchema $testSchema,
		array $json,
		bool $expectedIsStrictlyValid,
		array $expectedMessageConstraints,
		bool $expectedIsPermissivelyOk,
		bool $expectedIsPermissivelyGood
	) {
		$validator = new JsonSchemaValidator( $testSchema );

		$strictValidationResult = $validator->validateStrictly( (object)$json );

		$this->assertSame( $expectedIsStrictlyValid, $strictValidationResult->isGood() );
		$this->assertSame( $expectedIsStrictlyValid, $strictValidationResult->isOK() );

		$permissiveValidationResult = $validator->validatePermissively( (object)$json );
		$actualMessageConstraints = array_map(
			static fn ( $message ) => $message->getParams()[2]['constraint'],
			$permissiveValidationResult->getMessages()
		);

		$this->assertSame( $expectedMessageConstraints, $actualMessageConstraints );
		$this->assertSame( $expectedIsPermissivelyOk, $permissiveValidationResult->isOk() );
		$this->assertSame( $expectedIsPermissivelyGood, $permissiveValidationResult->isGood() );
	}
}
