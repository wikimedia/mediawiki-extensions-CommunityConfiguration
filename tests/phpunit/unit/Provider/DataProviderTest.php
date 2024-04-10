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
 * @covers \MediaWiki\Extension\CommunityConfiguration\Provider\DataProvider
 * @covers \MediaWiki\Extension\CommunityConfiguration\Provider\AbstractProvider
 */
class DataProviderTest extends MediaWikiUnitTestCase {

	private function assertConfigStatusOK( stdClass $expectedConfig, StatusValue $configStatus ) {
		$this->assertStatusOK( $configStatus );
		$this->assertStatusValue( $expectedConfig, $configStatus );
	}

	public function testConstruct() {
		$provider = new DataProvider(
			'ProviderId',
			[ 'skipDashboardListing' => false ],
			$this->createNoOpMock( IConfigurationStore::class ),
			$this->createNoOpMock( IValidator::class )
		);
		$this->assertInstanceOf(
			DataProvider::class,
			$provider
		);
		$this->assertFalse( $provider->getOptionValue( 'skipDashboardListing' ) );
	}

	public function testGetId() {
		$provider = new DataProvider(
			'ProviderId',
			[ 'skipDashboardListing' => true ],
			$this->createNoOpMock( IConfigurationStore::class ),
			$this->createNoOpMock( IValidator::class )
		);

		$this->assertSame(
			'ProviderId',
			$provider->getId()
		);
	}

	public function testGetName() {
		$messageMock = $this->createNoOpMock( Message::class );

		$localizer = $this->createMock( DerivativeContext::class );
		$localizer->expects( $this->once() )
			->method( 'msg' )
			->with( 'communityconfiguration-providerid-title' )
			->willReturn( $messageMock );

		$provider = new DataProvider(
			'ProviderId',
			[ 'skipDashboardListing' => true ],
			$this->createNoOpMock( IConfigurationStore::class ),
			$this->createNoOpMock( IValidator::class )
		);

		$this->assertSame(
			$messageMock,
			$provider->getName( $localizer )
		);
	}

	public function testGetObjects() {
		$storeMock = $this->createNoOpMock( IConfigurationStore::class );
		$validatorMock = $this->createNoOpMock( IValidator::class );
		$provider = new DataProvider(
			'ProviderId',
			[ 'skipDashboardListing' => true ],
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
			[ 'skipDashboardListing' => true ],
			new StaticStore( new stdClass() ),
			$validatorMock
		);

		$this->assertConfigStatusOK( $defaultConfig, $provider->loadValidConfiguration() );
		$this->assertConfigStatusOK( $defaultConfig, $provider->loadValidConfigurationUncached() );
	}

	public function testLoadInvalidConfig() {
		$config = (object)[ 'Foo' => 42 ];

		$validatorMock = $this->createMock( IValidator::class );
		$validatorMock->expects( $this->exactly( 2 ) )
			->method( 'validatePermissively' )
			->with( $config )
			->willReturn( StatusValue::newFatal( 'june' ) );

		$provider = new DataProvider(
			'ProviderId',
			[ 'skipDashboardListing' => false ],
			new StaticStore( $config ),
			$validatorMock
		);

		$this->assertStatusError( 'june', $provider->loadValidConfiguration() );
		$this->assertStatusError( 'june', $provider->loadValidConfigurationUncached() );
	}

	public function testLoadFailedStoreCached() {
		$storeMock = $this->createMock( IConfigurationStore::class );
		$storeMock->expects( $this->once() )
			->method( 'loadConfiguration' )
			->willReturn( StatusValue::newFatal( 'june' ) );

		$provider = new DataProvider(
			'ProviderId',
			[ 'skipDashboardListing' => true ],
			$storeMock,
			$this->createNoOpMock( IValidator::class )
		);

		$this->assertStatusError( 'june', $provider->loadValidConfiguration() );
	}

	public function testLoadFailedStoreUncached() {
		$storeMock = $this->createMock( IConfigurationStore::class );
		$storeMock->expects( $this->once() )
			->method( 'loadConfigurationUncached' )
			->willReturn( StatusValue::newFatal( 'june' ) );

		$provider = new DataProvider(
			'ProviderId',
			[ 'skipDashboardListing' => true ],
			$storeMock,
			$this->createNoOpMock( IValidator::class )
		);

		$this->assertStatusError( 'june', $provider->loadValidConfigurationUncached() );
	}

	public function testStoreConfigValidNoSchemaSupport() {
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
			->willReturn( StatusValue::newGood() );
		$validatorMock->expects( $this->once() )
			->method( 'areSchemasSupported' )
			->willReturn( false );

		$provider = new DataProvider(
			'ProviderId',
			[ 'skipDashboardListing' => true ],
			$storeMock,
			$validatorMock
		);

		$status = $provider->storeValidConfiguration( (object)[ 'Foo' => 42 ], $authority );
		$this->assertStatusOK( $status );
	}

	public function testStoreConfigValidWithSchemaSupport() {
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
			->method( $this->anythingBut( 'validateStrictly', 'areSchemasSupported', 'getSchemaVersion' ) );
		$validatorMock->expects( $this->once() )
			->method( 'validateStrictly' )
			->willReturn( StatusValue::newGood() );
		$validatorMock->expects( $this->once() )
			->method( 'areSchemasSupported' )
			->willReturn( true );
		$validatorMock->expects( $this->once() )
			->method( 'getSchemaVersion' )
			->willReturn( $version );

		$provider = new DataProvider(
			'foo',
			[ 'skipDashboardListing' => false ],
			$storeMock,
			$validatorMock
		);

		$status = $provider->storeValidConfiguration( $configData, $authority, 'summary' );
		$this->assertStatusOK( $status );
	}

	public function testStoreConfigInvalid() {
		$authority = new UltimateAuthority( new UserIdentityValue( 1, 'Admin' ) );

		$storeMock = $this->createNoOpMock( IConfigurationStore::class );

		$validatorMock = $this->createMock( IValidator::class );
		$validatorMock->expects( $this->once() )
			->method( 'validateStrictly' )
			->willReturn( StatusValue::newFatal( 'june' ) );

		$provider = new DataProvider(
			'ProviderId',
			[ 'skipDashboardListing' => true ],
			$storeMock,
			$validatorMock
		);

		$status = $provider->storeValidConfiguration( (object)[ 'Foo' => 42 ], $authority );
		$this->assertStatusError( 'june', $status );
	}

	public function testGetOption() {
		$provider = new DataProvider(
			'ProviderId',
			[ 'skipDashboardListing' => true ],
			$this->createNoOpMock( IConfigurationStore::class ),
			$this->createNoOpMock( IValidator::class )
		);
		$this->assertTrue( $provider->getOptionValue( 'skipDashboardListing' ) );
		$this->assertNull( $provider->getOptionValue( 'nonExistentOption' ) );
	}
}
