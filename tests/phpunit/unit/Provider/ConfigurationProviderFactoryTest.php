<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use InvalidArgumentException;
use MediaWiki\Config\HashConfig;
use MediaWiki\Extension\CommunityConfiguration\Hooks\HookRunner;
use MediaWiki\Extension\CommunityConfiguration\Provider\ConfigurationProviderFactory;
use MediaWiki\Extension\CommunityConfiguration\Store\StoreFactory;
use MediaWiki\Extension\CommunityConfiguration\Validation\ValidatorFactory;
use MediaWiki\MediaWikiServices;
use MediaWiki\Registration\ExtensionRegistry;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Extension\CommunityConfiguration\Provider\ConfigurationProviderFactory
 */
class ConfigurationProviderFactoryTest extends MediaWikiUnitTestCase {

	private function getExtensionRegistry() {
		$extensionRegistry = $this->createMock( ExtensionRegistry::class );
		$extensionRegistry->expects( $this->exactly( 2 ) )
			->method( 'getAttribute' )
			->willReturn( [] );
		return $extensionRegistry;
	}

	public function testSupportedKeys() {
		$providerConfigTemplate = [
			'store' => [
				'type' => 'wikipage',
				'args' => [ 'MediaWiki:Foo.json' ],
			],
			'validator' => [
				'type' => 'noop',
			],
		];
		$factory = new ConfigurationProviderFactory(
			$this->createNoOpMock( StoreFactory::class ),
			$this->createNoOpMock( ValidatorFactory::class ),
			new HashConfig( [
				'CommunityConfigurationProviders' => [
					'foo' => $providerConfigTemplate,
					'bar' => $providerConfigTemplate,
				],
				'CommunityConfigurationProviderClasses' => [],
			] ),
			$this->getExtensionRegistry(),
			$this->createNoOpMock( HookRunner::class, [ 'onCommunityConfigurationProvider_initList' ] ),
			$this->createNoOpMock( MediaWikiServices::class )
		);

		$this->assertEquals(
			[ 'foo', 'bar' ],
			$factory->getSupportedKeys()
		);
		$this->assertTrue( $factory->isProviderSupported( 'foo' ) );
		$this->assertTrue( $factory->isProviderSupported( 'bar' ) );
		$this->assertFalse( $factory->isProviderSupported( 'baz' ) );
	}

	public function testUnknownProvider() {
		$factory = new ConfigurationProviderFactory(
			$this->createNoOpMock( StoreFactory::class ),
			$this->createNoOpMock( ValidatorFactory::class ),
			new HashConfig( [
				'CommunityConfigurationProviders' => [],
				'CommunityConfigurationProviderClasses' => [],
			] ),
			$this->getExtensionRegistry(),
			$this->createNoOpMock( HookRunner::class, [ 'onCommunityConfigurationProvider_initList' ] ),
			$this->createNoOpMock( MediaWikiServices::class )
		);

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Provider foo is not supported' );
		$factory->newProvider( 'foo' );
	}

	public function testMalformedProvider() {
		$factory = new ConfigurationProviderFactory(
			$this->createNoOpMock( StoreFactory::class ),
			$this->createNoOpMock( ValidatorFactory::class ),
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
			$this->createNoOpMock( HookRunner::class, [ 'onCommunityConfigurationProvider_initList' ] ),
			$this->createNoOpMock( MediaWikiServices::class )
		);

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Wrong type for "store" property' );
		$factory->newProvider( 'foo' );
	}
}
