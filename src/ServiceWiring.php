<?php

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\CommunityConfiguration\Access\WikiPageConfigReader;
use MediaWiki\Extension\CommunityConfiguration\CommunityConfigurationServices;
use MediaWiki\Extension\CommunityConfiguration\Provider\ConfigurationProviderFactory;
use MediaWiki\Extension\CommunityConfiguration\Store\StoreFactory;
use MediaWiki\Extension\CommunityConfiguration\Store\WikiPage\Loader;
use MediaWiki\Extension\CommunityConfiguration\Store\WikiPage\Writer;
use MediaWiki\Extension\CommunityConfiguration\Utils;
use MediaWiki\Extension\CommunityConfiguration\Validation\ValidatorFactory;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;

return [
	'CommunityConfiguration.ProviderFactory' => static function ( MediaWikiServices $services ) {
		$ccServices = CommunityConfigurationServices::wrap( $services );
		return new ConfigurationProviderFactory(
			new ServiceOptions(
				ConfigurationProviderFactory::CONSTRUCTOR_OPTIONS,

				new HashConfig( [
					'CommunityConfigurationProviders' => Utils::getMergedAttribute(
						$services->getMainConfig(),
						'CommunityConfigurationProviders'
					),
					'CommunityConfigurationProviderClasses' => Utils::getMergedAttribute(
						$services->getMainConfig(),
						'CommunityConfigurationProviderClasses'
					),
				] ),
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
	'CommunityConfiguration.WikiPageConfigReader' => static function ( MediaWikiServices $services ) {
		$ccServices = CommunityConfigurationServices::wrap( $services );
		$reader = new WikiPageConfigReader(
			$services->getLocalServerObjectCache(),
			$ccServices->getConfigurationProviderFactory(),
			$services->getMainConfig()
		);
		$reader->setLogger( LoggerFactory::getInstance( 'CommunityConfiguration' ) );
		return $reader;
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
