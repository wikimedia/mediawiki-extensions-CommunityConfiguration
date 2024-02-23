<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use ApiUsageException;
use FormatJson;
use MediaWiki\Extension\CommunityConfiguration\CommunityConfigurationServices;
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

		$this->setMwGlobals( [
			'wgCommunityConfigurationProviders' => [
				'foo' => [
					'store' => [
						'type' => 'wikipage',
						'args' => [ 'MediaWiki:Foo.json' ],
					],
					'validator' => [
						'type' => 'noop',
					],
				],
			],
		] );
	}

	public function testExecuteOK() {
		$newConfig = [ 'Foo' => 42 ];
		$provider = CommunityConfigurationServices::wrap( $this->getServiceContainer() )
			->getConfigurationProviderFactory()
			->newProvider( 'foo' );

		$ret = $this->doApiRequestWithToken(
			[
				'action' => 'communityconfigurationedit',
				'provider' => 'foo',
				'content' => FormatJson::encode( $newConfig ),
				'summary' => 'testing'
			],
			null,
			$this->getTestSysop()->getAuthority(),
			'csrf'
		);
		$this->assertSame( 'success', $ret[0]['communityconfigurationedit']['result'] );
		$status = $provider->loadValidConfiguration();
		$this->assertTrue( $status->isOK() );
		$this->assertSame(
			$newConfig,
			$status->getValue()
		);
	}

	public function testInvalidProvider() {
		$this->expectException( ApiUsageException::class );
		$this->expectExceptionMessage( 'Unrecognized value for parameter "provider": bar.' );

		$this->doApiRequestWithToken(
			[
				'action' => 'communityconfigurationedit',
				'provider' => 'bar',
				'content' => FormatJson::encode( [ 'Foo' => 42 ] ),
				'summary' => 'testing'
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
				'summary' => 'testing'
			],
			null,
			$this->getTestSysop()->getAuthority(),
			'csrf'
		);
	}

	public function testNoPermission() {
		$this->expectException( ApiUsageException::class );
		$this->doApiRequestWithToken(
			[
				'action' => 'communityconfigurationedit',
				'provider' => 'foo',
				'content' => FormatJson::encode( [ 'Foo' => 42 ] ),
				'summary' => 'testing'
			],
			null,
			$this->getTestUser()->getAuthority(),
			'csrf'
		);
	}
}
