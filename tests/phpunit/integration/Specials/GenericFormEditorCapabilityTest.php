<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use HamcrestPHPUnitIntegration;
use MediaWiki\Context\DerivativeContext;
use MediaWiki\Context\IContextSource;
use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\CommunityConfiguration\EditorCapabilities\GenericFormEditorCapability;
use MediaWiki\Request\FauxRequest;
use MediaWiki\Title\Title;
use MediaWikiIntegrationTestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \MediaWiki\Extension\CommunityConfiguration\EditorCapabilities\GenericFormEditorCapability
 * @group Database
 */
class GenericFormEditorCapabilityTest extends MediaWikiIntegrationTestCase {
	use HamcrestPHPUnitIntegration;

	private const PROVIDER_ID = 'CommunityFeatureOverrides';
	private const CONFIG_PAGE_TITLE = 'MediaWiki:CommunityFeatureOverrides.json';

	private const SPEC = [
		'class' => GenericFormEditorCapability::class,
		'services' => [
			'CommunityConfiguration.ProviderFactory',
			'LinkRenderer',
			'FormatterFactory',
			'CommunityConfiguration.HookRunner',
			'CommunityConfiguration.MessagesProcessor',
		]
	];

	public function testCreation() {
		$objectFactory = $this->getServiceContainer()->getObjectFactory();

		$sut = $objectFactory->createObject(
			self::SPEC,
			[
				'extraArgs' => [ $this->getContext(), Title::newFromText( 'Special:CommunityConfiguration' ) ],
			]
		);

		$this->assertInstanceOf( GenericFormEditorCapability::class, $sut );
	}

	public function testLoadsOkForNonExistingConfigPage() {
		$this->setMwGlobals( [
			'wgCommunityConfigurationFeedbackURL' => 'https://bug-reporting.tool',
		] );

		[
			'testContext' => $testContext,
			'genericFormEditorCapability' => $genericFormEditorCapability,
		] = $this->getGenericFormEditorCapability();
		$mockLogger = $this->createMock( LoggerInterface::class );
		$mockLogger->expects( $this->never() )
			->method( $this->anything() );
		$genericFormEditorCapability->setLogger( $mockLogger );

		$genericFormEditorCapability->execute( self::PROVIDER_ID );

		$output = $testContext->getOutput();
		$this->assertSame(
			'Editing (communityconfiguration-communityfeatureoverrides-title)',
			$output->getPageTitle()
		);
		$this->assertThatHamcrest(
			'Shows the anchor to load the vue editor',
			$output->getHTML(),
			is( htmlPiece( havingChild(
				tagMatchingOutline( '<div id="ext-communityConfiguration-app-root"></div>' )
			) ) )
		);
		$jsConfigVars = $output->getJsConfigVars()['communityConfigurationData'];
		$this->assertSame( self::PROVIDER_ID, $jsConfigVars['providerId'] );
		$this->assertEquals(
			(object)[
				// default value
				'FeatureEnabled' => false,
				'FeatureActivationRegex' => '',
			],
			$jsConfigVars['data']
		);
		$this->assertSame( [
			'i18nPrefix' => 'communityconfiguration-' . strtolower( self::PROVIDER_ID ),
			'i18nMessages' => [],
			'feedbackURL' => 'https://bug-reporting.tool',
			'canEdit' => false,
		], $jsConfigVars['config'] );
	}

	public function testLoadsOkForConfigWithExtraProp() {
		[
			'testContext' => $testContext,
			'genericFormEditorCapability' => $genericFormEditorCapability,
		] = $this->getGenericFormEditorCapability( json_encode( [ 'Extra' => 'not in Schema' ] ) );

		$mockLogger = $this->createMock( LoggerInterface::class );
		$mockLogger->expects( $this->once() )
			->method( 'warning' );
		$genericFormEditorCapability->setLogger( $mockLogger );

		$genericFormEditorCapability->execute( self::PROVIDER_ID );

		$output = $testContext->getOutput();
		$this->assertThatHamcrest(
			'Shows the anchor to load the vue editor',
			$output->getHTML(),
			is( htmlPiece( havingChild(
				tagMatchingOutline( '<div id="ext-communityConfiguration-app-root"></div>' )
			) ) )
		);
		$jsConfigVars = $output->getJsConfigVars()['communityConfigurationData'];
		$this->assertEquals( (object)[
			// default value
			'FeatureEnabled' => false,
			'Extra' => 'not in Schema',
			'FeatureActivationRegex' => '',
		], $jsConfigVars['data'] );
	}

	public function testShowsErrorForInvalidConfig() {
		[
			'testContext' => $testContext,
			'genericFormEditorCapability' => $genericFormEditorCapability,
		] = $this->getGenericFormEditorCapability( json_encode( [ 'FeatureEnabled' => 'not a boolean' ] ) );
		$mockLogger = $this->createMock( LoggerInterface::class );
		$mockLogger->expects( $this->once() )
			->method( 'error' );
		$genericFormEditorCapability->setLogger( $mockLogger );

		$genericFormEditorCapability->execute( self::PROVIDER_ID );

		$output = $testContext->getOutput();
		$html = $output->getHTML();
		$this->assertThatHamcrest(
			'Shows an error response',
			$html,
			is( htmlPiece( havingChild( both( withTagName( 'h2' ) )->andAlso(
				havingTextContents( '(communityconfiguration-invalid-stored-config-error-details-headline)' )
			) ) ) )
		);
	}

	private function getContext(): IContextSource {
		$context = new DerivativeContext( RequestContext::getMain() );
		$context->setRequest( new FauxRequest() );
		$context->setLanguage( 'qqx' );

		// Make sure the skin context is correctly set https://phabricator.wikimedia.org/T200771
		$context->getSkin()->setContext( $context );

		return $context;
	}

	private function getGenericFormEditorCapability( string $preExistingConfig = null ): array {
		$configPage = $this->getNonexistingTestPage( self::CONFIG_PAGE_TITLE );
		if ( $preExistingConfig !== null ) {
			$this->editPage( $configPage, $preExistingConfig );
		}

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
			],
		] );

		$testContext = $this->getContext();

		return [
			'testContext' => $testContext,
			'genericFormEditorCapability' => $this->getServiceContainer()->getObjectFactory()->createObject(
				self::SPEC,
				[
					'extraArgs' => [ $testContext, Title::newFromText( 'Special:CommunityConfiguration' ) ],
				]
			),
		];
	}
}
