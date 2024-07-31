<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use Generator;
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
	private const NO_VALIDATION_PROVIDER_ID = 'NoValidationData';
	private const STATIC_PROVIDER_ID = 'StaticData';
	private const CONFIG_PAGE_TITLE = 'MediaWiki:CommunityFeatureOverrides.json';

	/**
	 * @var string[]
	 *
	 * List of providers that cannot be edited by GenericFormEditorCapability.
	 */
	private const UNEDITABLE_PROVIDERS = [ self::SKIPPED_PROVIDER_ID ];
	private const PROVIDERS = [
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
			'options' => [
				'excludeFromUI' => true,
			]
		],
		self::NO_VALIDATION_PROVIDER_ID => [
			'store' => [
				'type' => 'wikipage',
				'args' => [ 'MediaWiki:NoValidationData.json' ]
			],
			'validator' => [
				'type' => 'noop-with-schema',
			],
			'type' => 'data',
		],
		self::STATIC_PROVIDER_ID => [
			'store' => [
				'type' => 'static',
				'args' => [ [
					'Foo' => 42,
				] ]
			],
			'validator' => [
				'type' => 'noop-with-schema',
			],
			'type' => 'data',
		]
	];

	protected function setUp(): void {
		parent::setUp();

		$this->overrideConfigValues( [
			'CommunityConfigurationValidators' => array_merge(
				$this->getServiceContainer()->getMainConfig()->get( 'CommunityConfigurationValidators' ),
				[
					'noop-with-schema' => [
						'class' => NoopValidatorWithSchemaForTesting::class,
						'services' => [],
					],
				]
			),
			'CommunityConfigurationProviders' => self::PROVIDERS,
		] );
	}

	protected function newSpecialPage(): SpecialCommunityConfiguration {
		$ccServices = CommunityConfigurationServices::wrap( $this->getServiceContainer() );
		return new SpecialCommunityConfiguration(
			$ccServices->getEditorCapabilityFactory(),
			$ccServices->getConfigurationProviderFactory()
		);
	}

	public static function provideEditableProviderIds(): Generator {
		foreach ( self::PROVIDERS as $providerId => $providerSpec ) {
			if ( in_array( $providerId, self::UNEDITABLE_PROVIDERS ) ) {
				continue;
			}
			yield $providerId => [ $providerId ];
		}
	}

	/**
	 * @param string $providerId
	 * @dataProvider provideEditableProviderIds
	 */
	public function testExecuteEditor( string $providerId ): void {
		[ $output ] = $this->executeSpecialPage( $providerId );

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

		foreach ( self::PROVIDERS as $providerId => $providerSpec ) {
			if ( $providerId === self::SKIPPED_PROVIDER_ID ) {
				continue;
			}
			$this->assertThatHamcrest(
				'Shows the box for the normal provider',
				$output,
				is( htmlPiece( havingChild( both( withTagName( 'a' ) )->andAlso(
					havingTextContents( '(communityconfiguration-' . strtolower( $providerId ) . '-title)' )
				) ) ) )
			);
		}

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
