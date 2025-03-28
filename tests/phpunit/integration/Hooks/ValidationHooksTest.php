<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use MediaWiki\Extension\CommunityConfiguration\Store\WikiPageStore;
use MediaWiki\Json\FormatJson;
use MediaWikiIntegrationTestCase;

/**
 * @covers \MediaWiki\Extension\CommunityConfiguration\Hooks\ValidationHooks
 * @group Database
 */
class ValidationHooksTest extends MediaWikiIntegrationTestCase {
	protected function setUp(): void {
		parent::setUp();

		$this->overrideConfigValue( 'CommunityConfigurationProviders', [
			'foo' => [
				'store' => [
					'type' => 'wikipage',
					'args' => [ 'MediaWiki:Foo.json' ],
				],
				'validator' => [
					'type' => 'jsonschema',
					'args' => [
						JsonSchemaForTesting::class,
					],
				],
			],
			// used to test ValidationHooks does not have issues with other stores than wikipage
			'bar' => [
				'store' => [
					'type' => 'static',
					'args' => [ (object)[ 'Foo' => 42 ] ],
				],
				'validator' => [
					'type' => 'noop',
				],
			],
		] );
	}

	public function testSaveOtherPage(): void {
		$this->assertStatusOK( $this->editPage(
			'MediaWiki:Bar.json',
			FormatJson::encode( [
				'Number' => 'value',
				'Foo' => 42,
			] )
		) );
	}

	public function testValidSave(): void {
		$this->assertStatusOK( $this->editPage( 'MediaWiki:Foo.json', FormatJson::encode( [
			'NumberWithDefault' => 42,
		] ) ) );
	}

	public function testInvalidSave(): void {
		$status = $this->editPage( 'MediaWiki:Foo.json', FormatJson::encode( [
			'Number' => 'value',
		] ) );
		$this->assertStatusError(
			'communityconfiguration-schema-validation-error',
			$status
		);
	}

	/**
	 * A version that is not even a string is rejected before it reaches the validator, where
	 * the ?string parameter would make it a TypeError.
	 */
	public function testMalformedVersionSave(): void {
		$status = $this->editPage( 'MediaWiki:Foo.json', FormatJson::encode( [
			'NumberWithDefault' => 42,
			WikiPageStore::VERSION_FIELD_NAME => 1.1,
		] ) );
		$this->assertStatusError(
			'communityconfiguration-malformed-schema-version',
			$status
		);
	}

	public function testInvalidVersionSave(): void {
		$status = $this->editPage( 'MediaWiki:Foo.json', FormatJson::encode( [
			'NumberWithDefault' => 42,
			'$version' => '999.0.0',
		] ) );
		$this->assertStatusError(
			'communityconfiguration-invalid-schema-version',
			$status
		);
	}
}
