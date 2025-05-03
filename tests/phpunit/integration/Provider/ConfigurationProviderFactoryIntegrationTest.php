<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use InvalidArgumentException;
use MediaWiki\Extension\CommunityConfiguration\CommunityConfigurationServices;
use MediaWiki\Extension\CommunityConfiguration\Provider\DataProvider;
use MediaWiki\Extension\CommunityConfiguration\Provider\IConfigurationProvider;
use MediaWiki\Extension\CommunityConfiguration\Provider\MediaWikiConfigProvider;
use MediaWikiIntegrationTestCase;

/**
 * @covers \MediaWiki\Extension\CommunityConfiguration\Provider\ConfigurationProviderFactory
 */
class ConfigurationProviderFactoryIntegrationTest extends MediaWikiIntegrationTestCase {

	private const TYPE_TO_CLASS_MAP = [
		'data' => DataProvider::class,
		'mw-config' => MediaWikiConfigProvider::class,
	];

	public static function provideProviderTypes() {
		foreach ( self::TYPE_TO_CLASS_MAP as $key => $value ) {
			yield [ $key ];
		}
	}

	/**
	 * @param string $providerType
	 * @dataProvider provideProviderTypes
	 */
	public function testProviderTypes( string $providerType ) {
		$this->overrideConfigValue( 'CommunityConfigurationProviders', [
			$providerType => [
				'store' => [
					'type' => 'wikipage',
					'args' => [ 'MediaWiki:Bar.json' ],
				],
				'validator' => [
					'type' => 'jsonschema',
					'args' => [
						JsonSchemaForTesting::class,
					],
				],
				'type' => $providerType,
			],
		] );

		$provider = CommunityConfigurationServices::wrap( $this->getServiceContainer() )
			->getConfigurationProviderFactory()
			->newProvider( $providerType );
		$this->assertInstanceOf( IConfigurationProvider::class, $provider );
		$this->assertInstanceOf( self::TYPE_TO_CLASS_MAP[$providerType], $provider );
	}

	public function testUnrecognizedProvider() {
		$this->overrideConfigValue( 'CommunityConfigurationProviders', [
			'unknown' => [
				'store' => [
					'type' => 'wikipage',
					'args' => [ 'MediaWiki:Bar.json' ],
				],
				'validator' => [
					'type' => 'jsonschema',
					'args' => [
						JsonSchemaForTesting::class,
					],
				],
				'type' => 'unrecognized-type',
			],
		] );

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Provider class unrecognized-type is not supported' );
		CommunityConfigurationServices::wrap( $this->getServiceContainer() )
			->getConfigurationProviderFactory()
			->newProvider( 'unknown' );
	}

	public function testProviderWithService() {
		$this->overrideConfigValue( 'CommunityConfigurationProviderClasses', [
			'foo' => [
				'class' => ProviderWithExtraServiceForTesting::class,
				'services' => [
					'UrlUtils',
				],
			],
		] );
		$this->overrideConfigValue( 'CommunityConfigurationProviders', [
			'foo' => [
				'store' => [ 'type' => 'static', 'args' => [ [] ] ],
				'validator' => [ 'type' => 'noop' ],
				'type' => 'foo',
			],
		] );

		$provider = CommunityConfigurationServices::wrap( $this->getServiceContainer() )
			->getConfigurationProviderFactory()
			->newProvider( 'foo' );
		$this->assertInstanceOf( ProviderWithExtraServiceForTesting::class, $provider );
	}
}
