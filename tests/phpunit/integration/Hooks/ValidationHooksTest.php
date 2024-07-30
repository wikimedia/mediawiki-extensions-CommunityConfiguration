<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

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
					]
				],
			],
			// used to test ValidationHooks does not have issues with other stores than wikipage
			'bar' => [
				'store' => [
					'type' => 'static',
					'args' => [ (object)[ 'Foo' => 42 ] ],
				],
				'validator' => [
					'type' => 'noop'
				],
			],
		] );
	}

	public function testSaveOtherPage() {
		$this->assertStatusOK( $this->editPage(
			'MediaWiki:Bar.json',
			FormatJson::encode( [
				'Number' => 'value',
				'Foo' => 42,
			] )
		) );
	}

	public function testValidSave() {
		$this->assertStatusOK( $this->editPage( 'MediaWiki:Foo.json', FormatJson::encode( [
			'NumberWithDefault' => 42,
		] ) ) );
	}

	public function testInvalidSave() {
		$status = $this->editPage( 'MediaWiki:Foo.json', FormatJson::encode( [
			'Number' => 'value',
		] ) );
		$this->assertStatusError(
			'communityconfiguration-schema-validation-error',
			$status
		);
	}
}
