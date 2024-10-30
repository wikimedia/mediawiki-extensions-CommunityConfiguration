<?php

use MediaWiki\Config\HashConfig;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\CommunityConfiguration\Access\MediaWikiConfigReader;
use MediaWiki\Extension\CommunityConfiguration\CommunityConfigurationServices;
use MediaWiki\Extension\CommunityConfiguration\EditorCapabilities\EditorCapabilityFactory;
use MediaWiki\Extension\CommunityConfiguration\EditorCapabilities\MessagesProcessor;
use MediaWiki\Extension\CommunityConfiguration\EmergencyShutdown\EmergencyDefaultsPathBuilder;
use MediaWiki\Extension\CommunityConfiguration\EmergencyShutdown\EmergencyDefaultsUpdater;
use MediaWiki\Extension\CommunityConfiguration\Hooks\HookRunner;
use MediaWiki\Extension\CommunityConfiguration\Provider\ConfigurationProviderFactory;
use MediaWiki\Extension\CommunityConfiguration\Schema\SchemaConverterFactory;
use MediaWiki\Extension\CommunityConfiguration\Schema\SchemaMigrator;
use MediaWiki\Extension\CommunityConfiguration\Store\StoreFactory;
use MediaWiki\Extension\CommunityConfiguration\Store\WikiPage\Writer;
use MediaWiki\Extension\CommunityConfiguration\Utils;
use MediaWiki\Extension\CommunityConfiguration\Validation\ValidatorFactory;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;

return [
	'CommunityConfiguration.MessagesProcessor' => static function ( MediaWikiServices $services ) {
		return new MessagesProcessor(
			CommunityConfigurationServices::wrap( $services )->getLogger(),
			RequestContext::getMain()
		);
	},
	'CommunityConfiguration.EditorCapabilityFactory' => static function ( MediaWikiServices $services ) {
		return new EditorCapabilityFactory(
			new ServiceOptions(
				EditorCapabilityFactory::CONSTRUCTOR_OPTIONS,
				new HashConfig( [
					'CommunityConfigurationEditorCapabilities' => Utils::getMergedAttribute(
						$services->getMainConfig(), $services->getExtensionRegistry(),
						'CommunityConfigurationEditorCapabilities'
					),
				] )
			),
			$services->getObjectFactory(),
			CommunityConfigurationServices::wrap( $services )->getLogger()
		);
	},
	'CommunityConfiguration.EmergencyDefaultsUpdater' => static function ( MediaWikiServices $services ) {
		return new EmergencyDefaultsUpdater(
			CommunityConfigurationServices::wrap( $services )->getEmergencyDefaultsPathBuilder()
		);
	},
	'CommunityConfiguration.EmergencyDefaultsPathBuilder' => static function ( MediaWikiServices $services ) {
		return new EmergencyDefaultsPathBuilder(
			$services->getExtensionRegistry()
		);
	},
	'CommunityConfiguration.HookRunner' => static function ( MediaWikiServices $services ) {
		return new HookRunner(
			$services->getHookContainer()
		);
	},
	'CommunityConfiguration.Logger' => static function ( MediaWikiServices $services ) {
		return LoggerFactory::getInstance( 'CommunityConfiguration' );
	},
	'CommunityConfiguration.ProviderFactory' => static function ( MediaWikiServices $services ) {
		$ccServices = CommunityConfigurationServices::wrap( $services );
		return new ConfigurationProviderFactory(
			$ccServices->getLogger(),
			$ccServices->getStoreFactory(),
			$ccServices->getValidatorFactory(),
			$services->getMainConfig(),
			$services->getExtensionRegistry(),
			$ccServices->getHookRunner(),
			$services,
		);
	},
	'CommunityConfiguration.MediaWikiConfigReader' => static function ( MediaWikiServices $services ) {
		$ccServices = CommunityConfigurationServices::wrap( $services );
		return new MediaWikiConfigReader(
			$services->getLocalServerObjectCache(),
			$ccServices->getConfigurationProviderFactory(),
			$services->getMainConfig(),
			$ccServices->getLogger()
		);
	},
	'CommunityConfiguration.ValidatorFactory' => static function ( MediaWikiServices $services ) {
		return new ValidatorFactory(
			new ServiceOptions( ValidatorFactory::CONSTRUCTOR_OPTIONS, $services->getMainConfig() ),
			$services->getObjectFactory()
		);
	},
	'CommunityConfiguration.SchemaConverterFactory' => static function ( MediaWikiServices $services ) {
		return new SchemaConverterFactory(
			$services->getObjectFactory()
		);
	},
	'CommunityConfiguration.SchemaMigrator' => static function ( MediaWikiServices $services ) {
		$ccServices = CommunityConfigurationServices::wrap( $services );
		return new SchemaMigrator(
			$ccServices->getSchemaConverterFactory()
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
	},
];
