<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use IBufferingStatsdDataFactory;
use MediaWiki\Extension\CommunityConfiguration\Schema\JsonSchema;
use MediaWiki\Extension\CommunityConfiguration\Schema\JsonSchemaBuilder;
use MediaWiki\Extension\CommunityConfiguration\Schema\JsonSchemaReader;
use MediaWiki\Settings\Source\ReflectionSchemaSource;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Extension\CommunityConfiguration\Schema\JsonSchemaBuilder
 */
class JsonSchemaBuilderTest extends MediaWikiUnitTestCase {

	public function testSchemaProperties() {
		$schemaSource = $this->createNoOpMock( ReflectionSchemaSource::class, [ 'loadAsSchema' ] );
		$schemaSource->expects( $this->exactly( 2 ) )
			->method( 'loadAsSchema' )
			->willReturn( [
				'type' => 'object',
				'$defs' => [],
				'properties' => [
					'ExampleNumber' => [
						JsonSchema::TYPE => JsonSchema::TYPE_NUMBER,
					],
				],
			] );

		$schemaReader = $this->createNoOpMock( JsonSchemaReader::class, [
			'assertIsSchema',
			'getJsonSchemaVersion',
			'getSchemaId',
			'getReflectionSchemaSource',
		] );

		$schemaReader->expects( $this->exactly( 2 ) )
			->method( 'assertIsSchema' );
		$schemaReader->expects( $this->exactly( 2 ) )
			->method( 'getJsonSchemaVersion' )
			->willReturn( 'schema/version' );
		$schemaReader->expects( $this->exactly( 2 ) )
			->method( 'getSchemaId' )
			->willReturn( 'schema/id' );
		$schemaReader->expects( $this->exactly( 2 ) )
			->method( 'getReflectionSchemaSource' )
			->willReturn( $schemaSource );

		$builder = new JsonSchemaBuilder(
			$this->createMock( IBufferingStatsdDataFactory::class ),
			$schemaReader
		);

		$this->assertEquals( [
			'$schema' => 'schema/version',
			'$id' => 'schema/id',
			JsonSchema::ADDITIONAL_PROPERTIES => false,
			'type' => 'object',
			'$defs' => [],
			'properties' => [
				'ExampleNumber' => [
					JsonSchema::TYPE => JsonSchema::TYPE_NUMBER,
				],
			],
		], $builder->getRootSchema() );

		$this->assertEquals( [
			'ExampleNumber' => [
				JsonSchema::TYPE => JsonSchema::TYPE_NUMBER,
			],
		], $builder->getRootProperties() );
	}

	public function testDefaultsMap() {
		$schemaSource = $this->createNoOpMock( ReflectionSchemaSource::class, [ 'loadAsSchema' ] );
		$schemaSource->expects( $this->once() )
			->method( 'loadAsSchema' )
			->willReturn( [
				'properties' => [
					'number' => [
						'type' => 'number',
						'default' => 42,
					],
					'string' => [
						'type' => 'string',
						'default' => 'bar',
					],
					'objectArray' => [
						'type' => 'object',
						'default' => [
							'foo' => 1,
							'bar' => 2,
						],
					],
					'objectSubschema' => [
						'type' => 'object',
						'properties' => [
							'abc' => [
								'type' => 'number',
								'default' => 123,
							],
							'xyz' => [
								'type' => 'string',
								'default' => 'str',
							],
						]
					],
					'objectBoth' => [
						'type' => 'object',
						'properties' => [
							'foo' => [
								'type' => 'number',
								'default' => 42,
							]
						],
						'default' => [
							'foo' => 1,
							'bar' => 2,
						],
					],
				]
			] );

		$schemaReader = $this->createMock( JsonSchemaReader::class );
		$schemaReader->expects( $this->once() )
			->method( 'getReflectionSchemaSource' )
			->willReturn( $schemaSource );

		$builder = new JsonSchemaBuilder(
			$this->createMock( IBufferingStatsdDataFactory::class ),
			$schemaReader
		);
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
}
