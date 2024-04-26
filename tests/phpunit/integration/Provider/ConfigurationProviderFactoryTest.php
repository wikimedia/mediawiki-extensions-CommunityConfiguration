<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use InvalidArgumentException;
use MediaWiki\Extension\CommunityConfiguration\CommunityConfigurationServices;
use MediaWiki\Extension\CommunityConfiguration\Provider\DataProvider;
use MediaWiki\Extension\CommunityConfiguration\Provider\IConfigurationProvider;
use MediaWiki\Extension\CommunityConfiguration\Provider\WikiPageConfigProvider;
use MediaWikiIntegrationTestCase;

/**
 * @covers \MediaWiki\Extension\CommunityConfiguration\Provider\ConfigurationProviderFactory
 */
class ConfigurationProviderFactoryTest extends MediaWikiIntegrationTestCase {

	private const TYPE_TO_CLASS_MAP = [
		'data' => DataProvider::class,
		'mw-config' => WikiPageConfigProvider::class,
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
		$this->setMwGlobals( [
			'wgCommunityConfigurationProviders' => [
				$providerType => [
					'store' => [
						'type' => 'wikipage',
						'args' => [ 'MediaWiki:Bar.json' ],
					],
					'validator' => [
						'type' => 'jsonschema',
						'args' => [
							JsonSchemaForTesting::class,
						]
					],
					'type' => $providerType,
				]
			],
		] );

		$provider = CommunityConfigurationServices::wrap( $this->getServiceContainer() )
			->getConfigurationProviderFactory()
			->newProvider( $providerType );
		$this->assertInstanceOf( IConfigurationProvider::class, $provider );
		$this->assertInstanceOf( self::TYPE_TO_CLASS_MAP[$providerType], $provider );
	}

	public function testUnrecognizedProvider() {
		$this->setMwGlobals( [
			'wgCommunityConfigurationProviders' => [
				'unknown' => [
					'store' => [
						'type' => 'wikipage',
						'args' => [ 'MediaWiki:Bar.json' ],
					],
					'validator' => [
						'type' => 'jsonschema',
						'args' => [
							JsonSchemaForTesting::class,
						]
					],
					'type' => 'unrecognized-type',
				]
			],
		] );

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Provider class unrecognized-type is not supported' );
		CommunityConfigurationServices::wrap( $this->getServiceContainer() )
			->getConfigurationProviderFactory()
			->newProvider( 'unknown' );
	}
}
