<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

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
			'assertIsJsonSchema',
			'getJsonSchemaVersion',
			'getSchemaId',
			'getReflectionSchemaSource',
		] );
		$schemaReader->expects( $this->exactly( 2 ) )
			->method( 'assertIsJsonSchema' );
		$schemaReader->expects( $this->exactly( 2 ) )
			->method( 'getJsonSchemaVersion' )
			->willReturn( 'schema/version' );
		$schemaReader->expects( $this->exactly( 2 ) )
			->method( 'getSchemaId' )
			->willReturn( 'schema/id' );
		$schemaReader->expects( $this->exactly( 2 ) )
			->method( 'getReflectionSchemaSource' )
			->willReturn( $schemaSource );

		$builder = new JsonSchemaBuilder( $schemaReader );

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
					'foo' => [
						'type' => 'number',
						'default' => 42,
					],
					'bar' => [
						'type' => 'string',
						'default' => 'bar',
					]
				],
			] );

		$schemaReader = $this->createMock( JsonSchemaReader::class );
		$schemaReader->expects( $this->once() )
			->method( 'getReflectionSchemaSource' )
			->willReturn( $schemaSource );

		$builder = new JsonSchemaBuilder( $schemaReader );
		$this->assertEquals( (object)[
			'foo' => 42,
			'bar' => 'bar',
		], $builder->getDefaultsMap() );
	}
}
