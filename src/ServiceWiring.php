<?php

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\CommunityConfiguration\CommunityConfigurationServices;
use MediaWiki\Extension\CommunityConfiguration\Provider\ConfigurationProviderFactory;
use MediaWiki\Extension\CommunityConfiguration\Store\StoreFactory;
use MediaWiki\Extension\CommunityConfiguration\Validation\ValidatorFactory;
use MediaWiki\MediaWikiServices;

return [
	'CommunityConfiguration.ProviderFactory' => static function ( MediaWikiServices $services ) {
		$ccServices = CommunityConfigurationServices::wrap( $services );
		return new ConfigurationProviderFactory(
			new ServiceOptions( ConfigurationProviderFactory::CONSTRUCTOR_OPTIONS, $services->getMainConfig() ),
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
			$services->getObjectFactory(),
			$services->getMainConfig()
		);
	}
];
