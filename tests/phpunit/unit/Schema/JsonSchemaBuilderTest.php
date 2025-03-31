<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use MediaWiki\Extension\CommunityConfiguration\Schema\JsonSchema;
use MediaWiki\Extension\CommunityConfiguration\Schema\JsonSchemaBuilder;
use MediaWiki\Extension\CommunityConfiguration\Schema\JsonSchemaReader;
use MediaWikiUnitTestCase;
use Wikimedia\Stats\IBufferingStatsdDataFactory;

/**
 * @covers \MediaWiki\Extension\CommunityConfiguration\Schema\JsonSchemaBuilder
 * phpcs:disable Generic.NamingConventions.UpperCaseConstantName.ClassConstantNotUpperCase
 */
class JsonSchemaBuilderTest extends MediaWikiUnitTestCase {

	public function testSchemaProperties(): void {
		$schema = new class() extends JsonSchema {
			public const ExampleNumber = [
				JsonSchema::TYPE => JsonSchema::TYPE_NUMBER,
			];
		};
		$builder = $this->getNewJsonSchemaBuilder( $schema );

		$actualRootSchema = $builder->getRootSchema();

		$this->assertEquals( [
			'$schema' => 'https://json-schema.org/draft-04/schema#',
			'$id' => 'schema/id',
			JsonSchema::ADDITIONAL_PROPERTIES => false,
			'type' => 'object',
			'properties' => [
				'ExampleNumber' => [
					JsonSchema::TYPE => JsonSchema::TYPE_NUMBER,
					JsonSchema::DEFAULT => null,
				],
			],
			'required' => [],
		], $actualRootSchema );

		$this->assertEquals( [
			'ExampleNumber' => [
				JsonSchema::TYPE => JsonSchema::TYPE_NUMBER,
				JsonSchema::DEFAULT => null,
			],
		], $builder->getRootProperties() );
	}

	public function testRequiredHandlingRootLevel(): void {
		$schema = new class() extends JsonSchema {
			public const ExampleNumber = [
				JsonSchema::TYPE => JsonSchema::TYPE_NUMBER,
			];

			public const ExampleObject = [
				JsonSchema::TYPE => JsonSchema::TYPE_OBJECT,
				JsonSchema::PROPERTIES => [
					'ExampleString' => [
						JsonSchema::TYPE => JsonSchema::TYPE_STRING,
					],
				],
				JsonSchema::REQUIRED => [ 'ExampleString' ],
			];

			protected const __REQUIRED = [ 'ExampleNumber' ];
		};
		$builder = $this->getNewJsonSchemaBuilder( $schema );

		$this->assertEquals(
			[
				'$schema' => 'https://json-schema.org/draft-04/schema#',
				'$id' => 'schema/id',
				JsonSchema::ADDITIONAL_PROPERTIES => false,
				'type' => 'object',
				'properties' => [
					'ExampleNumber' => [
						JsonSchema::TYPE => JsonSchema::TYPE_NUMBER,
						JsonSchema::DEFAULT => null,
					],
					'ExampleObject' => [
						JsonSchema::TYPE => JsonSchema::TYPE_OBJECT,
						JsonSchema::PROPERTIES => [
							'ExampleString' => [
								JsonSchema::TYPE => JsonSchema::TYPE_STRING,
							],
						],
						JsonSchema::DEFAULT => null,
						JsonSchema::REQUIRED => [ 'ExampleString' ],
					],
				],
				'required' => [ 'ExampleNumber' ],
			],
			$builder->getRootSchema()
		);

		$this->assertEquals(
			[
				'ExampleNumber' => [
					JsonSchema::TYPE => JsonSchema::TYPE_NUMBER,
					JsonSchema::DEFAULT => null,
				],
				'ExampleObject' => [
					JsonSchema::TYPE => JsonSchema::TYPE_OBJECT,
					JsonSchema::PROPERTIES => [
						'ExampleString' => [
							JsonSchema::TYPE => JsonSchema::TYPE_STRING,
						],
					],
					JsonSchema::DEFAULT => null,
					JsonSchema::REQUIRED => [ 'ExampleString' ],
				],
			],
			$builder->getRootProperties()
		);
	}

	public function testDefaultsMap(): void {
		$schema = new class() extends JsonSchema {
			public const number = [
				JsonSchema::TYPE => JsonSchema::TYPE_NUMBER,
				JsonSchema::DEFAULT => 42,
			];
			public const string = [
				JsonSchema::TYPE => JsonSchema::TYPE_STRING,
				JsonSchema::DEFAULT => 'bar',
			];
			public const objectArray = [
				JsonSchema::TYPE => JsonSchema::TYPE_OBJECT,
				JsonSchema::DEFAULT => [
					'foo' => 1,
					'bar' => 2,
				],
			];
			public const objectSubschema = [
				JsonSchema::TYPE => JsonSchema::TYPE_OBJECT,
				JsonSchema::PROPERTIES => [
					'abc' => [
						JsonSchema::TYPE => JsonSchema::TYPE_NUMBER,
						JsonSchema::DEFAULT => 123,
					],
					'xyz' => [
						JsonSchema::TYPE => JsonSchema::TYPE_STRING,
						JsonSchema::DEFAULT => 'str',
					],
				],
			];
			public const objectBoth = [
				JsonSchema::TYPE => JsonSchema::TYPE_OBJECT,
				JsonSchema::PROPERTIES => [
					'foo' => [
						JsonSchema::TYPE => JsonSchema::TYPE_NUMBER,
						JsonSchema::DEFAULT => 42,
					],
				],
				JsonSchema::DEFAULT => [
					'foo' => 1,
					'bar' => 2,
				],
			];
		};
		$builder = $this->getNewJsonSchemaBuilder( $schema );

		$this->assertEquals( (object)[
			'number' => 42,
			'string' => 'bar',
			'objectArray' => (object)[
				'foo' => 1,
				'bar' => 2,
			],
			'objectSubschema' => (object)[
				'abc' => '123',
				'xyz' => 'str',
			],
			'objectBoth' => (object)[
				'foo' => 42,
				'bar' => 2,
			],
		], $builder->getDefaultsMap() );
	}

	private function getNewJsonSchemaBuilder( JsonSchema $schema ): JsonSchemaBuilder {
		$schemaReader = $this->getMockBuilder( JsonSchemaReader::class )
			->setConstructorArgs( [ $schema ] )
			->onlyMethods( [ 'getSchemaId' ] )
			->getMock();
		$schemaReader->method( 'getSchemaId' )
			->willReturn( 'schema/id' );
		return new JsonSchemaBuilder(
			$this->createMock( IBufferingStatsdDataFactory::class ),
			$schemaReader
		);
	}

}
