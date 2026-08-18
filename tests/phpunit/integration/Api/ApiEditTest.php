<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use MediaWiki\Api\ApiUsageException;
use MediaWiki\Extension\CommunityConfiguration\CommunityConfigurationServices;
use MediaWiki\Json\FormatJson;
use MediaWiki\Tests\Api\ApiTestCase;

/**
 * @covers \MediaWiki\Extension\CommunityConfiguration\Api\ApiEdit
 * @group Database
 * @group medium
 */
class ApiEditTest extends ApiTestCase {

	/**
	 * @inheritDoc
	 */
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
					'args' => [ JsonSchemaForTesting::class ],
				],
			],
		] );
	}

	public function testExecuteOK() {
		$provider = CommunityConfigurationServices::wrap( $this->getServiceContainer() )
			->getConfigurationProviderFactory()
			->newProvider( 'foo' );

		$ret = $this->doApiRequestWithToken(
			[
				'action' => 'communityconfigurationedit',
				'provider' => 'foo',
				'content' => FormatJson::encode( [ 'NumberWithDefault' => 42 ] ),
				'summary' => 'testing',
			],
			null,
			$this->getTestSysop()->getAuthority(),
			'csrf'
		);
		$this->assertSame( 'success', $ret[0]['communityconfigurationedit']['result'] );
		$status = $provider->loadValidConfiguration();
		$this->assertStatusOK( $status );
		$this->assertStatusValue(
			(object)[
				'NumberWithDefault' => 42,
				'Mentors' => (object)[],
			],
			$status
		);
	}

	public function testInvalidProvider() {
		$this->expectException( ApiUsageException::class );
		$this->expectExceptionMessage( 'Unrecognized value for parameter "provider": bar.' );

		$this->doApiRequestWithToken(
			[
				'action' => 'communityconfigurationedit',
				'provider' => 'bar',
				'content' => FormatJson::encode( [ 'NumberWithDefault' => 42 ] ),
				'summary' => 'testing',
			],
			null,
			$this->getTestSysop()->getAuthority(),
			'csrf'
		);
	}

	public function testNotJSON() {
		$this->expectException( ApiUsageException::class );
		$this->expectExceptionMessage( 'Syntax error' );

		$this->doApiRequestWithToken(
			[
				'action' => 'communityconfigurationedit',
				'provider' => 'foo',
				'content' => 'most certainly not valid JSON',
				'summary' => 'testing',
			],
			null,
			$this->getTestSysop()->getAuthority(),
			'csrf'
		);
	}

	public function testInvalidJSON() {
		$this->expectException( ApiUsageException::class );
		$this->doApiRequestWithToken(
			[
				'action' => 'communityconfigurationedit',
				'provider' => 'foo',
				'content' => FormatJson::encode( [ 'Number' => 'not a number' ] ),
				'summary' => 'testing',
			],
			null,
			$this->getTestSysop()->getAuthority(),
			'csrf'
		);
	}

	public function testMultipleValidationErrors() {
		try {
			$this->doApiRequestWithToken(
				[
					'action' => 'communityconfigurationedit',
					'provider' => 'foo',
					'content' => FormatJson::encode( [
						'NumberWithDefault' => 'not a number',
						'Mentors' => 'not an object',
					] ),
					'summary' => 'testing',
				],
				null,
				$this->getTestSysop()->getAuthority(),
				'csrf'
			);
			$this->fail( 'Expected an ApiUsageException to be thrown' );
		} catch ( ApiUsageException $e ) {
			$this->assertTrue(
				$e->getStatusValue()->hasMessage( 'communityconfiguration-schema-validation-error' )
			);
		}
	}

	public function testBrokenProvider() {
		// A provider with an invalid spec is included in getAllowedParams(), but
		// constructing it throws; execute() should convert that into an API error.
		$this->overrideConfigValue( 'CommunityConfigurationProviders', [
			'broken' => [
				'store' => false,
				'validator' => false,
			],
		] );

		$this->expectException( ApiUsageException::class );
		$this->expectExceptionMessage( 'Wrong type for "store" property for "broken" provider' );

		$this->doApiRequestWithToken(
			[
				'action' => 'communityconfigurationedit',
				'provider' => 'broken',
				'content' => FormatJson::encode( [ 'NumberWithDefault' => 42 ] ),
				'summary' => 'testing',
			],
			null,
			$this->getTestSysop()->getAuthority(),
			'csrf'
		);
	}

	public function testNoPermission() {
		$provider = CommunityConfigurationServices::wrap( $this->getServiceContainer() )
			->getConfigurationProviderFactory()
			->newProvider( 'foo' );

		try {
			$this->doApiRequestWithToken(
				[
					'action' => 'communityconfigurationedit',
					'provider' => 'foo',
					'content' => FormatJson::encode( [ 'NumberWithDefault' => 42 ] ),
					'summary' => 'testing',
				],
				null,
				$this->getTestUser()->getAuthority(),
				'csrf'
			);
			$this->fail( 'Expected an ApiUsageException to be thrown' );
		} catch ( ApiUsageException $e ) {
			$this->assertFalse(
				$e->getStatusValue()->hasMessage( 'communityconfiguration-schema-validation-error' ),
				'The request should fail with a permission error, not a validation error'
			);
		}

		$status = $provider->loadValidConfiguration();
		$this->assertStatusOK( $status );
		$this->assertSame( 0, $status->getValue()->NumberWithDefault );
	}
}
