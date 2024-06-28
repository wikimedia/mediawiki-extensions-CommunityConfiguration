<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use IBufferingStatsdDataFactory;
use MediaWiki\Extension\CommunityConfiguration\Schema\JsonSchema;
use MediaWiki\Extension\CommunityConfiguration\Schema\JsonSchemaBuilder;
use MediaWiki\Extension\CommunityConfiguration\Validation\JsonSchemaValidator;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Extension\CommunityConfiguration\Validation\JsonSchemaValidator
 * @covers \MediaWiki\Extension\CommunityConfiguration\Validation\ValidationStatus
 * // phpcs:disable Generic.NamingConventions.UpperCaseConstantName.ClassConstantNotUpperCase
 */
class JsonSchemaValidatorTest extends MediaWikiUnitTestCase {

	private function newValidator( $schema ) {
		return new JsonSchemaValidator(
			$schema,
			$this->createMock( IBufferingStatsdDataFactory::class )
		);
	}

	public function testConstruct() {
		$this->assertInstanceOf(
			JsonSchemaValidator::class,
			$this->newValidator( JsonSchemaForTesting::class )
		);
	}

	public function testGetSchemaBuilder() {
		$this->assertInstanceOf(
			JsonSchemaBuilder::class,
			$this->newValidator( JsonSchemaForTesting::class )->getSchemaBuilder()
		);
	}

	public static function provideJSON(): iterable {
		yield 'OK' => [
			new class() extends JsonSchema {
				public const Number = [
					JsonSchema::TYPE => JsonSchema::TYPE_NUMBER,
				];
			},
			[ 'Number' => 1.2 ],
			true,
			[],
			true,
			true,
			[],
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
			[],
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
			[ [
				'property' => 'Number',
				'pointer' => '/Number',
				'messageLiteral' => 'String value found, but a number is required',
				'additionalData' => [
					'constraint' => 'type',
				],
			] ],
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
			[ [
				'property' => '',
				'pointer' => '',
				'messageLiteral' =>
					'The property Bar is not defined and the definition does not allow additional properties',
				'additionalData' => [
					'constraint' => 'additionalProp',
				],
			] ],
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
			[],
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
			[ [
				'property' => 'ProposedIntroLinks',
				'pointer' => '/ProposedIntroLinks',
				'messageLiteral' =>
					'The property Extra is not defined and the definition does not allow additional properties',
				'additionalData' => [
					'constraint' => 'additionalProp',
				],
			] ],
		];

		yield 'failed pattern match in nested object' => [
			new class() extends JsonSchema {
				public const ProposedIntroLinks = [
					JsonSchema::TYPE => JsonSchema::TYPE_OBJECT,
					JsonSchema::PROPERTIES => [
						'create' => [
							JsonSchema::TYPE => JsonSchema::TYPE_STRING,
							'pattern' => '^[a-z]+$',
						],
					],
				];
			},
			[ 'ProposedIntroLinks' => (object)[ 'create' => 'foo1' ] ],
			false,
			[ 'pattern' ],
			false,
			false,
			[ [
				'property' => 'ProposedIntroLinks.create',
				'pointer' => '/ProposedIntroLinks/create',
				'messageLiteral' => 'Does not match the regex pattern ^[a-z]+$',
				'additionalData' => [
					'constraint' => 'pattern',
				],
			] ],
		];

		yield 'exceeded max value in nested object in an array' => [
			new class() extends JsonSchema {
				public const ProposedIntroLinks = [
					JsonSchema::TYPE => JsonSchema::TYPE_ARRAY,
					JsonSchema::ITEMS => [
						JsonSchema::TYPE => JsonSchema::TYPE_OBJECT,
						JsonSchema::PROPERTIES => [
							'maxCount' => [
								JsonSchema::TYPE => JsonSchema::TYPE_NUMBER,
								JsonSchema::MAXIMUM => 3,
							],
						],
					],
				];
			},
			[ 'ProposedIntroLinks' => [
				(object)[ 'maxCount' => 10 ],
			] ],
			false,
			[ 'maximum' ],
			false,
			false,
			[ [
				'property' => 'ProposedIntroLinks[0].maxCount',
				'pointer' => '/ProposedIntroLinks/0/maxCount',
				'messageLiteral' => 'Must have a maximum value of 3',
				'additionalData' => [
					'constraint' => 'maximum',
				],
			] ],
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
			[],
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
			[ [
				'property' => 'Number',
				'pointer' => '/Number',
				'messageLiteral' => 'The property Number is required',
				'additionalData' => [
					'constraint' => 'required',
				],
			] ],
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
			[ [
				'property' => 'Number',
				'pointer' => '/Number',
				'messageLiteral' => 'The property Number is required',
				'additionalData' => [
					'constraint' => 'required',
				],
			] ],
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
			[],
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
			[ [
				'property' => 'OneOfThese',
				'pointer' => '/OneOfThese',
				'messageLiteral' => 'Does not have a value in the enumeration ["foo","bar"]',
				'additionalData' => [
					'constraint' => 'enum',
				],
			] ],
		];
	}

	/**
	 * @param JsonSchema $testSchema
	 * @param array $json
	 * @param bool $expectedIsStrictlyValid expected value for both isGood and isOK of the validation
	 * @param array $expectedMessageConstraints the constraints provided by the library for why validation fails
	 * @param bool $expectedIsPermissivelyOk
	 * @param bool $expectedIsPermissivelyGood
	 * @dataProvider provideJSON
	 */
	public function testValidate(
		JsonSchema $testSchema,
		array $json,
		bool $expectedIsStrictlyValid,
		array $expectedMessageConstraints,
		bool $expectedIsPermissivelyOk,
		bool $expectedIsPermissivelyGood,
		array $expectedErrorData = []
	) {
		$validator = $this->newValidator( $testSchema );

		$strictValidationResult = $validator->validateStrictly( (object)$json );

		$this->assertSame(
			$expectedIsStrictlyValid,
			$strictValidationResult->isGood(),
			'isGood() of strict validation should return the correct result'
		);
		$this->assertSame(
			$expectedIsStrictlyValid,
			$strictValidationResult->isOK(),
			'isOK() of strict validation should return the correct result'
		);

		$permissiveValidationResult = $validator->validatePermissively( (object)$json );
		$actualMessageConstraints = array_map(
			static fn ( $data ) => $data['additionalData']['constraint'],
			$permissiveValidationResult->getValidationErrorsData()
		);

		$this->assertSame( $expectedMessageConstraints, $actualMessageConstraints );
		$this->assertSame(
			$expectedIsPermissivelyOk,
			$permissiveValidationResult->isOk(),
			'isOK() of permissive validation should return the correct result'
		);
		$this->assertSame(
			$expectedIsPermissivelyGood,
			$permissiveValidationResult->isGood(),
			'isGood() of permissive validation should return the correct result'
		);
		$this->assertEquals( $expectedErrorData, $permissiveValidationResult->getValidationErrorsData() );
	}

	public function testGetSchemaVersion() {
		$validator = $this->newValidator( JsonSchemaForTesting::class );
		$this->assertSame(
			JsonSchemaForTesting::VERSION,
			$validator->getSchemaVersion()
		);
	}
}
