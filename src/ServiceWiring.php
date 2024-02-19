<?php

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\CommunityConfiguration\CommunityConfigurationServices;
use MediaWiki\Extension\CommunityConfiguration\Provider\ConfigurationProviderFactory;
use MediaWiki\Extension\CommunityConfiguration\Store\StoreFactory;
use MediaWiki\Extension\CommunityConfiguration\Store\WikiPage\Loader;
use MediaWiki\Extension\CommunityConfiguration\Store\WikiPage\Writer;
use MediaWiki\Extension\CommunityConfiguration\Validation\ValidatorFactory;
use MediaWiki\MediaWikiServices;

return [
	'CommunityConfiguration.ProviderFactory' => static function ( MediaWikiServices $services ) {
		$ccServices = CommunityConfigurationServices::wrap( $services );
		return new ConfigurationProviderFactory(
			// This is meant to use MainConfig for all CONSTRUCTOR_OPTIONS, with the exemption of
			// CommunityConfigurationProviders, which is merged from extension.json attributes
			// section and the MainConfig's version of the variable (in the event of a key
			// conflict, MainConfig has precedence).
			new ServiceOptions(
				ConfigurationProviderFactory::CONSTRUCTOR_OPTIONS,

				// NOTE: ServiceOptions merges all passed Config instances together (with the
				// restriction of CONSTRUCTOR_OPTIONS kept in mind); when a key is present in
				// multiple Config instances, the _first_ one takes precedence. To ensure the
				// merged version of CommunityConfigurationProviders gets used, the HashConfig
				// needs to come first.
				new HashConfig( [
					'CommunityConfigurationProviders' => array_merge(
						// NOTE: Unlike ServiceOptions, array_merge gives precedence to the
						// _last_ array. Consequently, the MainConfig version needs to come second.
						ExtensionRegistry::getInstance()
							->getAttribute( 'CommunityConfigurationProviders' ),
						$services->getMainConfig()->get( 'CommunityConfigurationProviders' )
					)
				] ),
				$services->getMainConfig(),
			),
			$ccServices->getStoreFactory(),
			$ccServices->getValidatorFactory(),
			$services
		);
	},
	'CommunityConfiguration.ValidatorFactory' => static function ( MediaWikiServices $services ) {
		return new ValidatorFactory(
			new ServiceOptions( ValidatorFactory::CONSTRUCTOR_OPTIONS, $services->getMainConfig() ),
			$services->getObjectFactory()
		);
	},
	'CommunityConfiguration.StoreFactory' => static function ( MediaWikiServices $services ) {
		return new StoreFactory(
			new ServiceOptions( StoreFactory::CONSTRUCTOR_OPTIONS, $services->getMainConfig() ),
			$services->getObjectFactory()
		);
	},
	'CommunityConfiguration.WikiPageStore.Loader' => static function ( MediaWikiServices $services ) {
		return new Loader(
			$services->getMainWANObjectCache(),
			$services->getRevisionLookup(),
			$services->getTitleFactory()
		);
	},
	'CommunityConfiguration.WikiPageStore.Writer' => static function ( MediaWikiServices $services ) {
		return new Writer(
			$services->getWikiPageFactory(),
			$services->getUserFactory(),
			$services->getHookContainer()
		);
	}
];
