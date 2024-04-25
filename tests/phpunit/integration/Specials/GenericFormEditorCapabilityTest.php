<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use HamcrestPHPUnitIntegration;
use MediaWiki\Context\DerivativeContext;
use MediaWiki\Context\IContextSource;
use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\CommunityConfiguration\Specials\GenericFormEditorCapability;
use MediaWiki\Request\FauxRequest;
use MediaWiki\Title\Title;
use MediaWikiIntegrationTestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \MediaWiki\Extension\CommunityConfiguration\Specials\GenericFormEditorCapability
 * @group Database
 */
class GenericFormEditorCapabilityTest extends MediaWikiIntegrationTestCase {
	use HamcrestPHPUnitIntegration;

	private const PROVIDER_ID = 'CommunityFeatureOverrides';
	private const CONFIG_PAGE_TITLE = 'MediaWiki:CommunityFeatureOverrides.json';

	public function testCreation() {
		$objectFactory = $this->getServiceContainer()->getObjectFactory();

		$sut = $objectFactory->createObject(
			GenericFormEditorCapability::SPEC,
			[
				'extraArgs' => [ $this->getContext(), Title::newFromText( 'Special:CommunityConfiguration' ) ],
			]
		);

		$this->assertInstanceOf( GenericFormEditorCapability::class, $sut );
	}

	public function testLoadsOkForNonExistingConfigPage() {
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
		$this->assertSame( self::PROVIDER_ID, $jsConfigVars['providerName'] );
		$this->assertEquals(
			(object)[
				// default value
				'FeatureEnabled' => false,
			],
			$jsConfigVars['data']
		);
		$this->assertSame( [
			'i18nPrefix' => 'communityconfiguration-' . strtolower( self::PROVIDER_ID ),
			'bugReportToolURL' => null,
		], $jsConfigVars['config'] );
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
				GenericFormEditorCapability::SPEC,
				[
					'extraArgs' => [ $testContext, Title::newFromText( 'Special:CommunityConfiguration' ) ],
				]
			),
		];
	}
}
