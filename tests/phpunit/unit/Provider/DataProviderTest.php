<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use MediaWiki\Context\DerivativeContext;
use MediaWiki\Extension\CommunityConfiguration\Provider\DataProvider;
use MediaWiki\Extension\CommunityConfiguration\Provider\IConfigurationProvider;
use MediaWiki\Extension\CommunityConfiguration\Provider\ProviderServicesContainer;
use MediaWiki\Extension\CommunityConfiguration\Schema\JsonSchema;
use MediaWiki\Extension\CommunityConfiguration\Schema\JsonSchemaBuilder;
use MediaWiki\Extension\CommunityConfiguration\Store\IConfigurationStore;
use MediaWiki\Extension\CommunityConfiguration\Store\StaticStore;
use MediaWiki\Extension\CommunityConfiguration\Validation\IValidator;
use MediaWiki\Extension\CommunityConfiguration\Validation\JsonSchemaValidator;
use MediaWiki\Extension\CommunityConfiguration\Validation\ValidationStatus;
use MediaWiki\Message\Message;
use MediaWiki\Permissions\UltimateAuthority;
use MediaWiki\Status\StatusFormatter;
use MediaWiki\User\UserIdentityValue;
use MediaWikiUnitTestCase;
use Psr\Log\LoggerInterface;
use StatusValue;
use stdClass;
use Wikimedia\Stats\NullStatsdDataFactory;
use Wikimedia\Stats\StatsFactory;

/**
 * @covers \MediaWiki\Extension\CommunityConfiguration\Provider\DataProvider
 * @covers \MediaWiki\Extension\CommunityConfiguration\Provider\AbstractProvider
 */
class DataProviderTest extends MediaWikiUnitTestCase {

	private function assertConfigStatusOK( stdClass $expectedConfig, StatusValue $configStatus ): void {
		$this->assertStatusOK( $configStatus );
		$this->assertStatusValue( $expectedConfig, $configStatus );
	}

	public function testConstruct(): void {
		$provider = new DataProvider(
			$this->createNoOpMock( ProviderServicesContainer::class ),
			'ProviderId',
			[ 'excludeFromUI' => false ],
			$this->createNoOpMock( IConfigurationStore::class ),
			$this->createNoOpMock( IValidator::class )
		);
		$this->assertInstanceOf(
			DataProvider::class,
			$provider
		);
		$this->assertFalse( $provider->getOptionValue( 'excludeFromUI' ) );
	}

	public function testGetId(): void {
		$provider = new DataProvider(
			$this->createNoOpMock( ProviderServicesContainer::class ),
			'ProviderId',
			[ 'excludeFromUI' => true ],
			$this->createNoOpMock( IConfigurationStore::class ),
			$this->createNoOpMock( IValidator::class )
		);

		$this->assertSame(
			'ProviderId',
			$provider->getId()
		);
	}

	public function testGetName(): void {
		$messageMock = $this->createNoOpMock( Message::class );

		$localizer = $this->createMock( DerivativeContext::class );
		$localizer->expects( $this->once() )
			->method( 'msg' )
			->with( 'communityconfiguration-providerid-title' )
			->willReturn( $messageMock );

		$provider = new DataProvider(
			$this->createNoOpMock( ProviderServicesContainer::class ),
			'ProviderId',
			[ 'excludeFromUI' => true ],
			$this->createNoOpMock( IConfigurationStore::class ),
			$this->createNoOpMock( IValidator::class )
		);

		$this->assertSame(
			$messageMock,
			$provider->getName( $localizer )
		);
	}

	public function testGetObjects(): void {
		$storeMock = $this->createNoOpMock( IConfigurationStore::class );
		$validatorMock = $this->createNoOpMock( IValidator::class );
		$provider = new DataProvider(
			$this->createNoOpMock( ProviderServicesContainer::class ),
			'ProviderId',
			[ 'excludeFromUI' => true ],
			$storeMock,
			$validatorMock
		);

		$this->assertSame(
			$storeMock,
			$provider->getStore()
		);
		$this->assertSame(
			$validatorMock,
			$provider->getValidator()
		);
	}

	public function testLoadConfig(): void {
		$defaultConfig = (object)[ 'Foo' => 42, 'Bar' => 'string' ];

		$schemaBuilderMock = $this->createMock( JsonSchemaBuilder::class );
		$schemaBuilderMock->expects( $this->exactly( 2 ) )
			->method( 'getDefaultsMap' )
			->willReturn( $defaultConfig );

		$validatorMock = $this->createMock( JsonSchemaValidator::class );
		$validatorMock->expects( $this->exactly( 4 ) )
			->method( 'areSchemasSupported' )
			->willReturn( true );
		$validatorMock->expects( $this->exactly( 4 ) )
			->method( 'getSchemaBuilder' )
			->willReturn( $schemaBuilderMock );
		$validatorMock->expects( $this->exactly( 2 ) )
			->method( 'validatePermissively' )
			->willReturn( ValidationStatus::newGood() );

		$provider = new DataProvider(
			$this->createNoOpMock( ProviderServicesContainer::class ),
			'ProviderId',
			[ 'excludeFromUI' => true ],
			new StaticStore( new stdClass() ),
			$validatorMock
		);

		$this->assertConfigStatusOK( $defaultConfig, $provider->loadValidConfiguration() );
		$this->assertConfigStatusOK( $defaultConfig, $provider->loadValidConfigurationUncached() );
	}

	public static function providePartialConfigTestingData(): iterable {
		yield 'Object with multiple fields' => [
			(object)[
				'link_recommendation' => (object)[
					'disabled' => true,
					'underlinkedWeight' => 0.5,
					'minimumLinkScore' => 0.6,
				],
			],
			(object)[
				'link_recommendation' => (object)[
					'disabled' => false,
				],
			],
			(object)[
				'link_recommendation' => (object)[
					'disabled' => false,
					'underlinkedWeight' => 0.5,
					'minimumLinkScore' => 0.6,
				],
			],
		];

		yield 'Array of strings with defaults' => [
			(object)[
				'AutoModeratorSkipUserGroups' => [
					"bot",
					"sysop",
				],
			],
			(object)[
				"AutoModeratorSkipUserGroups" => [
					"bot",
				],
			],
			(object)[
				"AutoModeratorSkipUserGroups" => [
					"bot",
				],
			],
		];

		yield 'Array of objects with partial data' => [
			(object)[
				"GEHelpPanelLinks" => [],
			],
			(object)[
				"GEHelpPanelLinks" => [
					[
						"text" => "Writing good articles",
						"title" => "Help:How_to_write_an_Article",
					],
					[
						"text" => "Just a link title, no page",
					],
					[
						"title" => "Help:link_title_is_missing",
					],
				],
			],
			(object)[
				"GEHelpPanelLinks" => [
					[
						"text" => "Writing good articles",
						"title" => "Help:How_to_write_an_Article",
					],
					[
						"text" => "Just a link title, no page",
					],
					[
						"title" => "Help:link_title_is_missing",
					],
				],
			],
		];

		yield 'Extra fields are preserved' => [
			(object)[
				'link_recommendation' => (object)[
					'disabled' => true,
					'underlinkedWeight' => 0.5,
					'minimumLinkScore' => 0.6,
				],
			],
			(object)[
				'link_recommendation' => (object)[
					'disabled' => false,
					'nested_extra' => 'bar',
				],
				'outer_extra' => 'foo',
			],
			(object)[
				'link_recommendation' => (object)[
					'disabled' => false,
					'underlinkedWeight' => 0.5,
					'minimumLinkScore' => 0.6,
					'nested_extra' => 'bar',
				],
				'outer_extra' => 'foo',
			],
		];

		yield '`null` as default value is preserved' => [
			(object)[
				'link_recommendation' => (object)[
					'disabled' => true,
					'learnmore' => null,
				],
			],
			(object)[
				'link_recommendation' => (object)[
					'disabled' => false,
				],
			],
			(object)[
				'link_recommendation' => (object)[
					'disabled' => false,
					'learnmore' => null,
				],
			],
		];
	}

	/**
	 * @dataProvider providePartialConfigTestingData
	 */
	public function testMergePartialConfigWithDefaults(
		stdClass $defaultConfig,
		stdClass $storedConfig,
		stdClass $expectedConfig
	): void {
		$schemaBuilderStub = $this->createStub( JsonSchemaBuilder::class );
		$schemaBuilderStub->method( 'getDefaultsMap' )
			->willReturn( $defaultConfig );

		$validatorStub = $this->createStub( JsonSchemaValidator::class );
		$validatorStub->method( 'areSchemasSupported' )
			->willReturn( true );
		$validatorStub->method( 'getSchemaBuilder' )
			->willReturn( $schemaBuilderStub );
		$validatorStub->method( 'validatePermissively' )
			->willReturn( ValidationStatus::newGood() );
		$provider = new DataProvider(
			$this->createNoOpMock( ProviderServicesContainer::class ),
			'ProviderId',
			[ 'excludeFromUI' => true ],
			new StaticStore( $storedConfig ),
			$validatorStub
		);

		$this->assertConfigStatusOK( $expectedConfig, $provider->loadValidConfigurationUncached() );
	}

	public static function provideLoadInvalidConfig() {
		return [
			[ 'debug' ],
			[ 'error' ],
		];
	}

	/**
	 * @dataProvider provideLoadInvalidConfig
	 */
	public function testLoadInvalidConfig( string $readValidationLogLevel ): void {
		$config = (object)[ 'Foo' => 42 ];

		$validatorStatus = ValidationStatus::newFatal( 'june' );
		$validatorMock = $this->createMock( IValidator::class );
		$validatorMock->expects( $this->exactly( 2 ) )
			->method( 'validatePermissively' )
			->with( $config )
			->willReturn( $validatorStatus );

		$statusFormatter = $this->createNoOpMock( StatusFormatter::class, [ 'getPsr3MessageAndContext' ] );
		$statusFormatter->expects( $this->exactly( 2 ) )
			->method( 'getPsr3MessageAndContext' )
			->with( $validatorStatus, [ 'providerId' => 'ProviderId' ] )
			->willReturn( [ 'Foo log', [ 'context' => 'data' ] ] );

		$providerServicesContainer = $this->createNoOpMock(
			ProviderServicesContainer::class,
			[ 'getStatusFormatter' ]
		);
		$providerServicesContainer->expects( $this->exactly( 2 ) )
			->method( 'getStatusFormatter' )
			->willReturn( $statusFormatter );

		$logger = $this->createNoOpMock( LoggerInterface::class, [ 'log' ] );
		$logger->expects( $this->exactly( 2 ) )
			->method( 'log' )
			->with( $readValidationLogLevel, 'Foo log', [ 'context' => 'data' ] );

		$provider = new DataProvider(
			$providerServicesContainer,
			'ProviderId',
			[
				IConfigurationProvider::OPTION_READ_VALIDATION_LOG_LEVEL => $readValidationLogLevel,
				'excludeFromUI' => false,
			],
			new StaticStore( $config ),
			$validatorMock
		);
		$provider->setLogger( $logger );

		$this->assertStatusError( 'june', $provider->loadValidConfiguration() );
		$this->assertStatusError( 'june', $provider->loadValidConfigurationUncached() );
	}

	public static function provideLoadFailedStore() {
		return [
			[ 'loadConfiguration', 'loadValidConfiguration' ],
			[ 'loadConfigurationUncached', 'loadValidConfigurationUncached' ],
		];
	}

	/**
	 * @dataProvider provideLoadFailedStore
	 */
	public function testLoadFailedStore( string $storeMethod, string $providerMethod ): void {
		$storeStatus = ValidationStatus::newFatal( 'june' );
		$storeMock = $this->createMock( IConfigurationStore::class, [ $storeMethod ] );
		$storeMock->expects( $this->once() )
			->method( $storeMethod )
			->willReturn( $storeStatus );

		$statusFormatter = $this->createMock( StatusFormatter::class );
		$statusFormatter->expects( $this->once() )
			->method( 'getPsr3MessageAndContext' )
			->with( $storeStatus, [ 'providerId' => 'ProviderId' ] )
			->willReturn( [ 'Foo', [ 'context' => 'data' ] ] );

		$providerServicesContainer = $this->createNoOpMock(
			ProviderServicesContainer::class,
			[ 'getStatusFormatter' ]
		);
		$providerServicesContainer->expects( $this->once() )
			->method( 'getStatusFormatter' )
			->willReturn( $statusFormatter );

		$logger = $this->createNoOpMock( LoggerInterface::class, [ 'error' ] );
		$logger->expects( $this->once() )
			->method( 'error' )
			->with( 'Foo', [ 'context' => 'data' ] );

		$provider = new DataProvider(
			$providerServicesContainer,
			'ProviderId',
			[ 'excludeFromUI' => true ],
			$storeMock,
			$this->createNoOpMock( IValidator::class )
		);
		$provider->setLogger( $logger );

		$this->assertStatusError( 'june', $provider->$providerMethod() );
	}

	public function testStoreConfigValidNoSchemaSupport(): void {
		$authority = new UltimateAuthority( new UserIdentityValue( 1, 'Admin' ) );

		$storeMock = $this->createMock( IConfigurationStore::class );
		$storeMock->expects( $this->never() )
			->method( $this->anythingBut( 'storeConfiguration' ) );
		$storeMock->expects( $this->once() )
			->method( 'storeConfiguration' )
			->with( (object)[ 'Foo' => 42 ], null, $authority, '' )
			->willReturn( StatusValue::newGood() );

		$validatorMock = $this->createMock( IValidator::class );
		$validatorMock->expects( $this->never() )
			->method( $this->anythingBut( 'validateStrictly', 'areSchemasSupported' ) );
		$validatorMock->expects( $this->once() )
			->method( 'validateStrictly' )
			->willReturn( ValidationStatus::newGood() );
		$validatorMock->expects( $this->exactly( 2 ) )
			->method( 'areSchemasSupported' )
			->willReturn( false );

		$provider = new DataProvider(
			$this->createNoOpMock( ProviderServicesContainer::class ),
			'ProviderId',
			[ 'excludeFromUI' => true ],
			$storeMock,
			$validatorMock
		);

		$status = $provider->storeValidConfiguration( (object)[ 'Foo' => 42 ], $authority );
		$this->assertStatusOK( $status );
	}

	public function testStoreConfigValidWithSchemaSupport(): void {
		$authority = new UltimateAuthority( new UserIdentityValue( 1, 'Admin' ) );
		$configData = (object)[ 'Foo' => 42 ];
		$version = '1.0.0';

		$storeMock = $this->createMock( IConfigurationStore::class );
		$storeMock->expects( $this->never() )
			->method( $this->anythingBut( 'storeConfiguration' ) );
		$storeMock->expects( $this->once() )
			->method( 'storeConfiguration' )
			->with(
				$configData,
				$version,
				$authority,
				'summary'
			)
			->willReturn( StatusValue::newGood() );

		$validatorMock = $this->createMock( IValidator::class );
		$validatorMock->expects( $this->never() )
			->method( $this->anythingBut(
				'validateStrictly',
				'areSchemasSupported',
				'getSchemaVersion',
				'getSchemaBuilder'
			) );
		$validatorMock->expects( $this->once() )
			->method( 'validateStrictly' )
			->willReturn( ValidationStatus::newGood() );
		$validatorMock->expects( $this->exactly( 2 ) )
			->method( 'areSchemasSupported' )
			->willReturn( true );
		$validatorMock->expects( $this->once() )
			->method( 'getSchemaVersion' )
			->willReturn( $version );

		$provider = new DataProvider(
			$this->createNoOpMock( ProviderServicesContainer::class ),
			'foo',
			[ 'excludeFromUI' => false ],
			$storeMock,
			$validatorMock
		);

		$status = $provider->storeValidConfiguration( $configData, $authority, 'summary' );
		$this->assertStatusOK( $status );
	}

	public function testStoreConfigInvalid(): void {
		$authority = new UltimateAuthority( new UserIdentityValue( 1, 'Admin' ) );

		$storeMock = $this->createNoOpMock( IConfigurationStore::class );

		$validatorMock = $this->createMock( IValidator::class );
		$validatorMock->expects( $this->once() )
			->method( 'validateStrictly' )
			->willReturn( ValidationStatus::newFatal( 'june' ) );

		$provider = new DataProvider(
			$this->createNoOpMock( ProviderServicesContainer::class ),
			'ProviderId',
			[ 'excludeFromUI' => true ],
			$storeMock,
			$validatorMock
		);

		$status = $provider->storeValidConfiguration( (object)[ 'Foo' => 42 ], $authority );
		$this->assertStatusError( 'june', $status );
	}

	public function testGetOption(): void {
		$provider = new DataProvider(
			$this->createNoOpMock( ProviderServicesContainer::class ),
			'ProviderId',
			[ 'excludeFromUI' => true ],
			$this->createNoOpMock( IConfigurationStore::class ),
			$this->createNoOpMock( IValidator::class )
		);
		$this->assertTrue( $provider->getOptionValue( 'excludeFromUI' ) );
		$this->assertNull( $provider->getOptionValue( 'nonExistentOption' ) );
	}

	public function testNormalizeStoredConfig(): void {
		$storedConfig = (object)[
			'Mentors' => [],
		];
		$expectedConfig = (object)[
			'Mentors' => (object)[],
		];
		$schema = new class() extends JsonSchema {
			// phpcs:ignore Generic.NamingConventions.UpperCaseConstantName.ClassConstantNotUpperCase
			public const Mentors = [
				JsonSchema::TYPE => JsonSchema::TYPE_OBJECT,
			];
		};

		$provider = new DataProvider(
			$this->createNoOpMock( ProviderServicesContainer::class ),
			'ProviderId',
			[ 'excludeFromUI' => true ],
			new StaticStore( $storedConfig ),
			new JsonSchemaValidator(
				$schema,
				new NullStatsdDataFactory(),
				StatsFactory::newNull()
			),
		);

		$this->assertConfigStatusOK( $expectedConfig, $provider->loadValidConfigurationUncached() );
	}

}
