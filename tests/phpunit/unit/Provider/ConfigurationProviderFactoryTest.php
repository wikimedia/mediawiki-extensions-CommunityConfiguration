<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use InvalidArgumentException;
use MediaWiki\Config\HashConfig;
use MediaWiki\Extension\CommunityConfiguration\Hooks\HookRunner;
use MediaWiki\Extension\CommunityConfiguration\Provider\ConfigurationProviderFactory;
use MediaWiki\Extension\CommunityConfiguration\Provider\IConfigurationProvider;
use MediaWiki\Extension\CommunityConfiguration\Provider\ProviderServicesContainer;
use MediaWiki\Extension\CommunityConfiguration\Store\StoreFactory;
use MediaWiki\Extension\CommunityConfiguration\Validation\ValidatorFactory;
use MediaWiki\Registration\ExtensionRegistry;
use MediaWikiUnitTestCase;
use Psr\Log\NullLogger;
use Wikimedia\ObjectFactory\ObjectFactory;

/**
 * @covers \MediaWiki\Extension\CommunityConfiguration\Provider\ConfigurationProviderFactory
 */
class ConfigurationProviderFactoryTest extends MediaWikiUnitTestCase {

	private const PROVIDER_CONFIG_TEMPLATE = [
		'store' => [
			'type' => 'wikipage',
			'args' => [ 'MediaWiki:Foo.json' ],
		],
		'validator' => [
			'type' => 'noop',
		],
	];

	private function getExtensionRegistry() {
		$extensionRegistry = $this->createMock( ExtensionRegistry::class );
		$extensionRegistry->expects( $this->exactly( 2 ) )
			->method( 'getAttribute' )
			->willReturn( [] );
		return $extensionRegistry;
	}

	public function testSupportedKeys() {
		$factory = new ConfigurationProviderFactory(
			new NullLogger(),
			$this->createNoOpMock( StoreFactory::class ),
			$this->createNoOpMock( ValidatorFactory::class ),
			$this->createNoOpMock( ProviderServicesContainer::class ),
			new HashConfig( [
				'CommunityConfigurationProviders' => [
					'foo' => self::PROVIDER_CONFIG_TEMPLATE,
					'bar' => self::PROVIDER_CONFIG_TEMPLATE,
				],
				'CommunityConfigurationProviderClasses' => [],
			] ),
			$this->getExtensionRegistry(),
			$this->createNoOpMock( ObjectFactory::class ),
			$this->createNoOpMock( HookRunner::class, [ 'onCommunityConfigurationProvider_initList' ] )
		);

		$this->assertEquals(
			[ 'foo', 'bar' ],
			$factory->getSupportedKeys()
		);
		$this->assertTrue( $factory->isProviderSupported( 'foo' ) );
		$this->assertTrue( $factory->isProviderSupported( 'bar' ) );
		$this->assertFalse( $factory->isProviderSupported( 'baz' ) );
	}

	public function testSupportedKeysWithUI() {
		$factory = new ConfigurationProviderFactory(
			new NullLogger(),
			$this->createNoOpMock( StoreFactory::class ),
			$this->createNoOpMock( ValidatorFactory::class ),
			$this->createNoOpMock( ProviderServicesContainer::class ),
			new HashConfig( [
				'CommunityConfigurationProviders' => [
					'withUI' => self::PROVIDER_CONFIG_TEMPLATE,
					'withUIExplicit' => [
							'options' => [
								IConfigurationProvider::OPTION_EXCLUDE_FROM_UI => false,
							],
						] + self::PROVIDER_CONFIG_TEMPLATE,
					'noUI' => [
						'options' => [
							IConfigurationProvider::OPTION_EXCLUDE_FROM_UI => true,
						],
					] + self::PROVIDER_CONFIG_TEMPLATE,
				],
				'CommunityConfigurationProviderClasses' => [],
			] ),
			$this->getExtensionRegistry(),
			$this->createNoOpMock( ObjectFactory::class ),
			$this->createNoOpMock( HookRunner::class, [ 'onCommunityConfigurationProvider_initList' ] )
		);

		$this->assertEquals(
			[ 'withUI', 'withUIExplicit' ],
			$factory->getSupportedKeysWithUI()
		);
	}

	public function testUnknownProvider() {
		$factory = new ConfigurationProviderFactory(
			new NullLogger(),
			$this->createNoOpMock( StoreFactory::class ),
			$this->createNoOpMock( ValidatorFactory::class ),
			$this->createNoOpMock( ProviderServicesContainer::class ),
			new HashConfig( [
				'CommunityConfigurationProviders' => [],
				'CommunityConfigurationProviderClasses' => [],
			] ),
			$this->getExtensionRegistry(),
			$this->createNoOpMock( ObjectFactory::class ),
			$this->createNoOpMock( HookRunner::class, [ 'onCommunityConfigurationProvider_initList' ] )
		);

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Provider foo is not supported' );
		$factory->newProvider( 'foo' );
	}

	public function testMalformedProvider() {
		$factory = new ConfigurationProviderFactory(
			new NullLogger(),
			$this->createNoOpMock( StoreFactory::class ),
			$this->createNoOpMock( ValidatorFactory::class ),
			$this->createNoOpMock( ProviderServicesContainer::class ),
			new HashConfig( [
				'CommunityConfigurationProviders' => [
					'foo' => [
						'store' => 123,
						'validator' => [
							'type' => 'noop',
						],
					],
				],
				'CommunityConfigurationProviderClasses' => [],
			] ),
			$this->getExtensionRegistry(),
			$this->createNoOpMock( ObjectFactory::class ),
			$this->createNoOpMock( HookRunner::class, [ 'onCommunityConfigurationProvider_initList' ] )
		);

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Wrong type for "store" property' );
		$factory->newProvider( 'foo' );
	}
}
