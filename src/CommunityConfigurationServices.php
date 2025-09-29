<?php

namespace MediaWiki\Extension\CommunityConfiguration;

use MediaWiki\Extension\CommunityConfiguration\Access\MediaWikiConfigReader;
use MediaWiki\Extension\CommunityConfiguration\Access\MediaWikiConfigRouter;
use MediaWiki\Extension\CommunityConfiguration\EditorCapabilities\EditorCapabilityFactory;
use MediaWiki\Extension\CommunityConfiguration\EmergencyShutdown\EmergencyDefaultsPathBuilder;
use MediaWiki\Extension\CommunityConfiguration\EmergencyShutdown\EmergencyDefaultsUpdater;
use MediaWiki\Extension\CommunityConfiguration\Hooks\HookRunner;
use MediaWiki\Extension\CommunityConfiguration\Provider\ConfigurationProviderFactory;
use MediaWiki\Extension\CommunityConfiguration\Provider\ProviderServicesContainer;
use MediaWiki\Extension\CommunityConfiguration\Schema\SchemaConverterFactory;
use MediaWiki\Extension\CommunityConfiguration\Schema\SchemaMigrator;
use MediaWiki\Extension\CommunityConfiguration\Store\StoreFactory;
use MediaWiki\Extension\CommunityConfiguration\Store\WikiPage\Writer;
use MediaWiki\Extension\CommunityConfiguration\Validation\ValidatorFactory;
use MediaWiki\MediaWikiServices;
use Psr\Log\LoggerInterface;

class CommunityConfigurationServices {

	private MediaWikiServices $coreServices;

	public function __construct( MediaWikiServices $coreServices ) {
		$this->coreServices = $coreServices;
	}

	/**
	 * Static version of the constructor, for nicer syntax.
	 */
	public static function wrap( MediaWikiServices $coreServices ): self {
		return new static( $coreServices );
	}

	public function getEditorCapabilityFactory(): EditorCapabilityFactory {
		return $this->coreServices->getService( 'CommunityConfiguration.EditorCapabilityFactory' );
	}

	public function getHookRunner(): HookRunner {
		return $this->coreServices->getService( 'CommunityConfiguration.HookRunner' );
	}

	public function getLogger(): LoggerInterface {
		return $this->coreServices->getService( 'CommunityConfiguration.Logger' );
	}

	public function getConfigurationProviderFactory(): ConfigurationProviderFactory {
		return $this->coreServices->getService( 'CommunityConfiguration.ProviderFactory' );
	}

	public function getProviderServicesContainer(): ProviderServicesContainer {
		return $this->coreServices->getService( 'CommunityConfiguration.ProviderServicesContainer' );
	}

	public function getEmergencyDefaultsUpdater(): EmergencyDefaultsUpdater {
		return $this->coreServices->getService( 'CommunityConfiguration.EmergencyDefaultsUpdater' );
	}

	public function getEmergencyDefaultsPathBuilder(): EmergencyDefaultsPathBuilder {
		return $this->coreServices->getService( 'CommunityConfiguration.EmergencyDefaultsPathBuilder' );
	}

	public function getMediaWikiConfigReader(): MediaWikiConfigReader {
		return $this->coreServices->getService( 'CommunityConfiguration.MediaWikiConfigReader' );
	}

	public function getMediaWikiConfigRouter(): MediaWikiConfigRouter {
		return $this->coreServices->getService( 'CommunityConfiguration.MediaWikiConfigRouter' );
	}

	public function getSchemaConverterFactory(): SchemaConverterFactory {
		return $this->coreServices->getService( 'CommunityConfiguration.SchemaConverterFactory' );
	}

	public function getSchemaMigrator(): SchemaMigrator {
		return $this->coreServices->getService( 'CommunityConfiguration.SchemaMigrator' );
	}

	public function getStoreFactory(): StoreFactory {
		return $this->coreServices->getService( 'CommunityConfiguration.StoreFactory' );
	}

	public function getValidatorFactory(): ValidatorFactory {
		return $this->coreServices->getService( 'CommunityConfiguration.ValidatorFactory' );
	}

	public function getWikiPageStoreWriter(): Writer {
		return $this->coreServices->getService( 'CommunityConfiguration.WikiPageStore.Writer' );
	}
}
