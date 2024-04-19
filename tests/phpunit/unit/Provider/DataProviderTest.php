<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use MediaWiki\Context\DerivativeContext;
use MediaWiki\Extension\CommunityConfiguration\Provider\DataProvider;
use MediaWiki\Extension\CommunityConfiguration\Schema\JsonSchemaBuilder;
use MediaWiki\Extension\CommunityConfiguration\Store\IConfigurationStore;
use MediaWiki\Extension\CommunityConfiguration\Store\StaticStore;
use MediaWiki\Extension\CommunityConfiguration\Validation\IValidator;
use MediaWiki\Extension\CommunityConfiguration\Validation\JsonSchemaValidator;
use MediaWiki\Message\Message;
use MediaWiki\Permissions\UltimateAuthority;
use MediaWiki\User\UserIdentityValue;
use MediaWikiUnitTestCase;
use StatusValue;
use stdClass;

/**
 * @coversDefaultClass \MediaWiki\Extension\CommunityConfiguration\Provider\DataProvider
 */
class DataProviderTest extends MediaWikiUnitTestCase {

	private function assertConfigStatusOK( stdClass $expectedConfig, StatusValue $configStatus ) {
		$this->assertStatusOK( $configStatus );
		$this->assertStatusValue( $expectedConfig, $configStatus );
	}

	/**
	 * @covers ::__construct
	 */
	public function testConstruct() {
		$this->assertInstanceOf(
			DataProvider::class,
			new DataProvider(
				'ProviderId',
				false,
				$this->createNoOpMock( IConfigurationStore::class ),
				$this->createNoOpMock( IValidator::class )
			)
		);
	}

	/**
	 * @covers ::getId
	 */
	public function testGetId() {
		$provider = new DataProvider(
			'ProviderId',
			true,
			$this->createNoOpMock( IConfigurationStore::class ),
			$this->createNoOpMock( IValidator::class )
		);

		$this->assertSame(
			'ProviderId',
			$provider->getId()
		);
	}

	/**
	 * @covers ::getName
	 */
	public function testGetName() {
		$messageMock = $this->createNoOpMock( Message::class );

		$localizer = $this->createMock( DerivativeContext::class );
		$localizer->expects( $this->once() )
			->method( 'msg' )
			->with( 'communityconfiguration-providerid-title' )
			->willReturn( $messageMock );

		$provider = new DataProvider(
			'ProviderId',
			true,
			$this->createNoOpMock( IConfigurationStore::class ),
			$this->createNoOpMock( IValidator::class )
		);

		$this->assertSame(
			$messageMock,
			$provider->getName( $localizer )
		);
	}

	/**
	 * @covers ::getStore
	 * @covers ::getValidator
	 */
	public function testGetObjects() {
		$storeMock = $this->createNoOpMock( IConfigurationStore::class );
		$validatorMock = $this->createNoOpMock( IValidator::class );
		$provider = new DataProvider(
			'ProviderId',
			true,
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

	/**
	 * @covers ::loadValidConfigurationUncached
	 * @covers ::loadValidConfiguration
	 * @covers ::processStoreStatus
	 * @covers ::validateConfiguration
	 */
	public function testLoadConfig() {
		$defaultConfig = (object)[ 'Foo' => 42, 'Bar' => 'string' ];

		$schemaBuilderMock = $this->createMock( JsonSchemaBuilder::class );
		$schemaBuilderMock->expects( $this->exactly( 2 ) )
			->method( 'getDefaultsMap' )
			->willReturn( $defaultConfig );

		$validatorMock = $this->createMock( JsonSchemaValidator::class );
		$validatorMock->expects( $this->exactly( 2 ) )
			->method( 'areSchemasSupported' )
			->willReturn( true );
		$validatorMock->expects( $this->exactly( 2 ) )
			->method( 'getSchemaBuilder' )
			->willReturn( $schemaBuilderMock );
		$validatorMock->expects( $this->exactly( 2 ) )
			->method( 'validatePermissively' )
			->willReturn( StatusValue::newGood() );

		$provider = new DataProvider(
			'ProviderId',
			true,
			new StaticStore( new stdClass() ),
			$validatorMock
		);

		$this->assertConfigStatusOK( $defaultConfig, $provider->loadValidConfiguration() );
		$this->assertConfigStatusOK( $defaultConfig, $provider->loadValidConfigurationUncached() );
	}

	/**
	 * @covers ::loadValidConfigurationUncached
	 * @covers ::loadValidConfiguration
	 * @covers ::processStoreStatus
	 * @covers ::validateConfiguration
	 */
	public function testLoadInvalidConfig() {
		$config = (object)[ 'Foo' => 42 ];

		$validatorMock = $this->createMock( IValidator::class );
		$validatorMock->expects( $this->exactly( 2 ) )
			->method( 'validatePermissively' )
			->with( $config )
			->willReturn( StatusValue::newFatal( 'june' ) );

		$provider = new DataProvider(
			'ProviderId',
			false,
			new StaticStore( $config ),
			$validatorMock
		);

		$this->assertStatusError( 'june', $provider->loadValidConfiguration() );
		$this->assertStatusError( 'june', $provider->loadValidConfigurationUncached() );
	}

	/**
	 * @covers ::loadValidConfiguration
	 * @covers ::processStoreStatus
	 * @covers ::validateConfiguration
	 */
	public function testLoadFailedStoreCached() {
		$storeMock = $this->createMock( IConfigurationStore::class );
		$storeMock->expects( $this->once() )
			->method( 'loadConfiguration' )
			->willReturn( StatusValue::newFatal( 'june' ) );

		$provider = new DataProvider(
			'ProviderId',
			true,
			$storeMock,
			$this->createNoOpMock( IValidator::class )
		);

		$this->assertStatusError( 'june', $provider->loadValidConfiguration() );
	}

	/**
	 * @covers ::loadValidConfiguration
	 * @covers ::processStoreStatus
	 * @covers ::validateConfiguration
	 */
	public function testLoadFailedStoreUncached() {
		$storeMock = $this->createMock( IConfigurationStore::class );
		$storeMock->expects( $this->once() )
			->method( 'loadConfigurationUncached' )
			->willReturn( StatusValue::newFatal( 'june' ) );

		$provider = new DataProvider(
			'ProviderId',
			true,
			$storeMock,
			$this->createNoOpMock( IValidator::class )
		);

		$this->assertStatusError( 'june', $provider->loadValidConfigurationUncached() );
	}

	/**
	 * @covers ::storeValidConfiguration
	 */
	public function testStoreConfigValid() {
		$authority = new UltimateAuthority( new UserIdentityValue( 1, 'Admin' ) );

		$storeMock = $this->createMock( IConfigurationStore::class );
		$storeMock->expects( $this->once() )
			->method( 'storeConfiguration' )
			->willReturn( StatusValue::newGood() );

		$validatorMock = $this->createMock( IValidator::class );
		$validatorMock->expects( $this->once() )
			->method( 'validateStrictly' )
			->willReturn( StatusValue::newGood() );

		$provider = new DataProvider(
			'ProviderId',
			true,
			$storeMock,
			$validatorMock
		);

		$status = $provider->storeValidConfiguration( (object)[ 'Foo' => 42 ], $authority );
		$this->assertStatusOK( $status );
	}

	/**
	 * @covers ::storeValidConfiguration
	 */
	public function testStoreConfigInvalid() {
		$authority = new UltimateAuthority( new UserIdentityValue( 1, 'Admin' ) );

		$storeMock = $this->createNoOpMock( IConfigurationStore::class );

		$validatorMock = $this->createMock( IValidator::class );
		$validatorMock->expects( $this->once() )
			->method( 'validateStrictly' )
			->willReturn( StatusValue::newFatal( 'june' ) );

		$provider = new DataProvider(
			'ProviderId',
			true,
			$storeMock,
			$validatorMock
		);

		$status = $provider->storeValidConfiguration( (object)[ 'Foo' => 42 ], $authority );
		$this->assertStatusError( 'june', $status );
	}

	/**
	 * @covers ::__construct
	 * @covers ::shouldSkipDashboardListing
	 */
	public function testShouldSkipDashboardFlagTrue() {
		$provider = new DataProvider(
			'exampleProvider',
			true,
			$this->createNoOpMock( IConfigurationStore::class ),
			$this->createNoOpMock( IValidator::class )
		);
		$this->assertTrue(
			$provider->shouldSkipDashboardListing(),
			'shouldSkipDashboardListing should return true when shouldSkipDashboard is set to true'
		);
	}

	/**
	 * @covers ::__construct
	 * @covers ::shouldSkipDashboardListing
	 */
	public function testShouldSkipDashboardFlagFalse() {
		$provider = new DataProvider(
			'exampleProvider',
			false,
			$this->createNoOpMock( IConfigurationStore::class ),
			$this->createNoOpMock( IValidator::class )
		);
		$this->assertFalse(
			$provider->shouldSkipDashboardListing(),
			'shouldSkipDashboardListing should return false when shouldSkipDashboard is set to false'
		);
	}

}
