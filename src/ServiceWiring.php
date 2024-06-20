<?php

use MediaWiki\Config\HashConfig;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\CommunityConfiguration\Access\MediaWikiConfigReader;
use MediaWiki\Extension\CommunityConfiguration\CommunityConfigurationServices;
use MediaWiki\Extension\CommunityConfiguration\EditorCapabilities\EditorCapabilityFactory;
use MediaWiki\Extension\CommunityConfiguration\EditorCapabilities\MessagesProcessor;
use MediaWiki\Extension\CommunityConfiguration\Hooks\HookRunner;
use MediaWiki\Extension\CommunityConfiguration\Provider\ConfigurationProviderFactory;
use MediaWiki\Extension\CommunityConfiguration\Store\StoreFactory;
use MediaWiki\Extension\CommunityConfiguration\Store\WikiPage\Writer;
use MediaWiki\Extension\CommunityConfiguration\Utils;
use MediaWiki\Extension\CommunityConfiguration\Validation\ValidatorFactory;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;

return [
	'CommunityConfiguration.MessagesProcessor' => static function ( MediaWikiServices $services ) {
		return new MessagesProcessor( RequestContext::getMain() );
	},
	'CommunityConfiguration.EditorCapabilityFactory' => static function ( MediaWikiServices $services ) {
		return new EditorCapabilityFactory(
			new ServiceOptions(
				EditorCapabilityFactory::CONSTRUCTOR_OPTIONS,
				new HashConfig( [
					'CommunityConfigurationEditorCapabilities' => Utils::getMergedAttribute(
						$services->getMainConfig(),
						'CommunityConfigurationEditorCapabilities'
					)
				] )
			),
			$services->getObjectFactory()
		);
	},
	'CommunityConfiguration.HookRunner' => static function ( MediaWikiServices $services ) {
		return new HookRunner(
			$services->getHookContainer()
		);
	},
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
	'CommunityConfiguration.MediaWikiConfigReader' => static function ( MediaWikiServices $services ) {
		$ccServices = CommunityConfigurationServices::wrap( $services );
		$reader = new MediaWikiConfigReader(
			$services->getLocalServerObjectCache(),
			$ccServices->getConfigurationProviderFactory(),
			$services->getMainConfig()
		);
		$reader->setLogger( LoggerFactory::getInstance( 'CommunityConfiguration' ) );
		return $reader;
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
	'CommunityConfiguration.WikiPageStore.Writer' => static function ( MediaWikiServices $services ) {
		return new Writer(
			$services->getWikiPageFactory(),
			$services->getUserFactory(),
			$services->getHookContainer()
		);
	}
];
