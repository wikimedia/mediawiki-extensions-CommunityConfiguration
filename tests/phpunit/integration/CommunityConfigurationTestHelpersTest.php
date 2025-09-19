<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use InvalidArgumentException;
use MediaWiki\Extension\CommunityConfiguration\CommunityConfigurationServices;
use MediaWikiIntegrationTestCase;

/**
 * @covers \MediaWiki\Extension\CommunityConfiguration\Tests\CommunityConfigurationTestHelpers
 */
class CommunityConfigurationTestHelpersTest extends MediaWikiIntegrationTestCase {
	use CommunityConfigurationTestHelpers;

	protected function setUp(): void {
		parent::setUp();
		$this->overriddenProviders = [];
	}

	public function testOverrideProviderOK() {
		$this->markTestSkippedIfExtensionNotLoaded( 'CommunityConfigurationExample' );

		$this->overrideProviderConfig( [
			'CCExample_String' => 'foo',
		], 'CommunityConfigurationExample' );

		$provider = CommunityConfigurationServices::wrap( $this->getServiceContainer() )
			->getConfigurationProviderFactory()
			->newProvider( 'CommunityConfigurationExample' );

		$status = $provider->loadValidConfiguration();
		$this->assertStatusOK( $status );

		$value = $status->getValue();
		$this->assertSame( 'foo', $value->CCExample_String );
	}

	public function testOverrideProviderUndefined() {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Provider InvalidProviderId is not supported' );

		$this->overrideProviderConfig( [], 'InvalidProviderId' );
	}
}
