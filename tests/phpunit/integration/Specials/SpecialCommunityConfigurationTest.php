<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use HamcrestPHPUnitIntegration;
use MediaWiki\Extension\CommunityConfiguration\CommunityConfigurationServices;
use MediaWiki\Extension\CommunityConfiguration\Specials\SpecialCommunityConfiguration;
use SpecialPageTestBase;

/**
 * @covers \MediaWiki\Extension\CommunityConfiguration\Specials\SpecialCommunityConfiguration
 * @group Database
 */
class SpecialCommunityConfigurationTest extends SpecialPageTestBase {
	use HamcrestPHPUnitIntegration;

	private const PROVIDER_ID = 'CommunityFeatureOverrides';
	private const SKIPPED_PROVIDER_ID = 'CommunityFeatureData';
	private const CONFIG_PAGE_TITLE = 'MediaWiki:CommunityFeatureOverrides.json';

	protected function setUp(): void {
		parent::setUp();

		$this->setMwGlobals( [
			'wgCommunityConfigurationProviders' => [
				self::PROVIDER_ID => [
					'store' => [
						'type' => 'wikipage',
						'args' => [ self::CONFIG_PAGE_TITLE ],
					],
					'validator' => [
						'type' => 'jsonschema',
						'args' => [ JsonConfigSchemaForTesting::class ],
					],
					'type' => 'mw-config',
				],
				self::SKIPPED_PROVIDER_ID => [
					'store' => [
						'type' => 'wikipage',
						'args' => [ 'MediaWiki:CommunityFeatureData.json' ],
					],
					'validator' => [
						'type' => 'jsonschema',
						'args' => [ JsonSchemaForTesting::class ],
					],
					'type' => 'data',
					'skipDashboardListing' => true,
				],
			],
		] );
	}

	protected function newSpecialPage(): SpecialCommunityConfiguration {
		$coreServices = $this->getServiceContainer();
		return new SpecialCommunityConfiguration(
			$coreServices->getObjectFactory(),
			CommunityConfigurationServices::wrap( $coreServices )->getConfigurationProviderFactory()
		);
	}

	public function testExecuteEditor(): void {
		[ $output ] = $this->executeSpecialPage( self::PROVIDER_ID );

		$this->assertThatHamcrest(
			"Shows the summary message",
			$output,
			is( htmlPiece( havingChild( both( withTagName( 'p' ) )
				->andAlso( havingTextContents( equalToIgnoringWhiteSpace( '(communityconfiguration-summary)' ) ) ) ) ) )
		);
		$this->assertThatHamcrest(
			'Shows the anchor to load the vue editor',
			$output,
			is( htmlPiece( havingChild(
				tagMatchingOutline( '<div id="ext-communityConfiguration-app-root"></div>' )
			) ) )
		);
	}

	public function testShowDashboard(): void {
		[ $output ] = $this->executeSpecialPage( null );

		$this->assertThatHamcrest(
			'Shows the box for the normal provider',
			$output,
			is( htmlPiece( havingChild( both( withTagName( 'a' ) )->andAlso(
				havingTextContents( '(communityconfiguration-' . strtolower( self::PROVIDER_ID ) . '-title)' )
			) ) ) )
		);
		$this->assertStringNotContainsString(
			'(communityconfiguration-' . strtolower( self::SKIPPED_PROVIDER_ID ) . '-title)',
			$output,
			'Does not show the box for the skipped provider'
		);
	}

	public function testSkippedProviderNotFoundError() {
		[ $output ] = $this->executeSpecialPage( self::SKIPPED_PROVIDER_ID );

		$this->assertThatHamcrest(
			'Shows error message when trying to access a skipped provider',
			$output,
			is( htmlPiece( havingChild( both( withTagName( 'p' ) )->andAlso(
				havingTextContents( '(communityconfiguration-provider-not-found: ' . self::SKIPPED_PROVIDER_ID . ')' )
			) ) ) )
		);
	}

	public function testProviderNotFoundError() {
		[ $output ] = $this->executeSpecialPage( 'NotExistingProvider' );

		$this->assertThatHamcrest(
			'Shows error message when trying to access a non-existing provider',
			$output,
			is( htmlPiece( havingChild( both( withTagName( 'p' ) )->andAlso(
				havingTextContents( '(communityconfiguration-provider-not-found: NotExistingProvider)' )
			) ) ) )
		);
	}
}
