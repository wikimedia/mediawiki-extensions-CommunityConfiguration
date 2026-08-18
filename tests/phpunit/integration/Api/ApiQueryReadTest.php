<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use MediaWiki\Api\ApiUsageException;
use MediaWiki\Extension\CommunityConfiguration\CommunityConfigurationServices;
use MediaWiki\Json\FormatJson;
use MediaWiki\Tests\Api\ApiTestCase;

/**
 * @covers \MediaWiki\Extension\CommunityConfiguration\Api\ApiQueryRead
 * @group Database
 * @group medium
 */
class ApiQueryReadTest extends ApiTestCase {

	private const PROVIDER_SPEC = [
		'store' => [
			'type' => 'wikipage',
			'args' => [ 'MediaWiki:Foo.json' ],
		],
		'validator' => [
			'type' => 'jsonschema',
			'args' => [ JsonSchemaForTesting::class ],
		],
	];

	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->overrideConfigValue( 'CommunityConfigurationProviders', [
			'foo' => self::PROVIDER_SPEC,
		] );
	}

	private function storeConfiguration( array $config ): void {
		$status = CommunityConfigurationServices::wrap( $this->getServiceContainer() )
			->getConfigurationProviderFactory()
			->newProvider( 'foo' )
			->storeValidConfiguration(
				(object)$config,
				$this->getTestSysop()->getAuthority(),
				'testing'
			);
		$this->assertStatusOK( $status );
	}

	public function testExecuteNoStoredConfig() {
		[ $ret ] = $this->doApiRequest( [
			'action' => 'query',
			'meta' => 'communityconfiguration',
			'ccrprovider' => 'foo',
		] );

		$data = $ret['communityconfiguration']['data'];
		$this->assertSame( 0, $data['NumberWithDefault'] );
		$this->assertSame( [], $data['Mentors'] );
		$this->assertArrayNotHasKey( 'version', $ret['communityconfiguration'] );
	}

	public function testExecuteVersionAssertionOK() {
		$this->storeConfiguration( [ 'NumberWithDefault' => 42 ] );

		[ $ret ] = $this->doApiRequest( [
			'action' => 'query',
			'meta' => 'communityconfiguration',
			'ccrprovider' => 'foo',
			'ccrassertversion' => JsonSchemaForTesting::VERSION,
		] );

		$this->assertSame( 42, $ret['communityconfiguration']['data']['NumberWithDefault'] );
		$this->assertSame(
			JsonSchemaForTesting::VERSION,
			$ret['communityconfiguration']['version']
		);
	}

	public function testExecuteVersionAssertionFailure() {
		$this->storeConfiguration( [ 'NumberWithDefault' => 42 ] );

		$this->expectException( ApiUsageException::class );
		$this->expectExceptionMessage(
			'Failed to assert version of the configuration matches the expected version'
		);

		$this->doApiRequest( [
			'action' => 'query',
			'meta' => 'communityconfiguration',
			'ccrprovider' => 'foo',
			'ccrassertversion' => '0.0.1',
		] );
	}

	public function testExecuteInvalidStoredConfig() {
		// Deregister the provider, so that ValidationHooks does not reject the
		// invalid configuration when saving it directly to the store page.
		$this->overrideConfigValue( 'CommunityConfigurationProviders', [] );
		$this->editPage(
			'MediaWiki:Foo.json',
			FormatJson::encode( [ 'NumberWithDefault' => 'not a number' ] )
		);
		$this->overrideConfigValue( 'CommunityConfigurationProviders', [
			'foo' => self::PROVIDER_SPEC,
		] );

		$this->expectException( ApiUsageException::class );
		$this->expectExceptionMessage( 'Key: NumberWithDefault' );

		$this->doApiRequest( [
			'action' => 'query',
			'meta' => 'communityconfiguration',
			'ccrprovider' => 'foo',
		] );
	}

	public function testExecuteUnknownProvider() {
		$this->expectException( ApiUsageException::class );
		$this->expectExceptionMessage( 'Unrecognized value for parameter "ccrprovider": bar.' );

		$this->doApiRequest( [
			'action' => 'query',
			'meta' => 'communityconfiguration',
			'ccrprovider' => 'bar',
		] );
	}

	public function testExecuteBrokenProvider() {
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

		$this->doApiRequest( [
			'action' => 'query',
			'meta' => 'communityconfiguration',
			'ccrprovider' => 'broken',
		] );
	}
}
