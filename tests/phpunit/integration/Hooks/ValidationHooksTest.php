<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use FormatJson;
use MediaWikiIntegrationTestCase;

/**
 * @covers \MediaWiki\Extension\CommunityConfiguration\Hooks\ValidationHooks
 * @group Database
 */
class ValidationHooksTest extends MediaWikiIntegrationTestCase {
	protected function setUp(): void {
		parent::setUp();

		$this->setMwGlobals( [
			'wgCommunityConfigurationProviders' => [
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
				'bar' => [
					'store' => [
						'type' => 'static',
						'args' => [ (object)[ 'Foo' => 42 ] ],
					],
					'validator' => [
						'type' => 'noop'
					],
				]
			],
		] );
	}

	public function testSaveOtherPage() {
		$this->assertStatusOK( $this->editPage(
			'MediaWiki:Bar.json',
			FormatJson::encode( [
				'Foo' => 'value',
				'Bar' => 42,
			] )
		) );
	}

	public function testValidSave() {
		$this->assertStatusOK( $this->editPage( 'MediaWiki:Foo.json', FormatJson::encode( [
			'Foo' => 42,
		] ) ) );
	}

	public function testInvalidSave() {
		$status = $this->editPage( 'MediaWiki:Foo.json', FormatJson::encode( [
			'Foo' => 'value',
		] ) );
		$this->assertStatusError(
			'communityconfiguration-schema-validation-error',
			$status
		);
	}
}
