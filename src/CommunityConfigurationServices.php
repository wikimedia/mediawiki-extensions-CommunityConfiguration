<?php

namespace MediaWiki\Extension\CommunityConfiguration;

use MediaWiki\Extension\CommunityConfiguration\Access\WikiPageConfigReader;
use MediaWiki\Extension\CommunityConfiguration\EditorCapabilities\EditorCapabilityFactory;
use MediaWiki\Extension\CommunityConfiguration\Provider\ConfigurationProviderFactory;
use MediaWiki\Extension\CommunityConfiguration\Store\StoreFactory;
use MediaWiki\Extension\CommunityConfiguration\Store\WikiPage\Loader;
use MediaWiki\Extension\CommunityConfiguration\Store\WikiPage\Writer;
use MediaWiki\Extension\CommunityConfiguration\Validation\ValidatorFactory;
use MediaWiki\MediaWikiServices;

class CommunityConfigurationServices {

	private MediaWikiServices $coreServices;

	/**
	 * @param MediaWikiServices $coreServices
	 */
	public function __construct( MediaWikiServices $coreServices ) {
		$this->coreServices = $coreServices;
	}

	/**
	 * Static version of the constructor, for nicer syntax.
	 * @param MediaWikiServices $coreServices
	 * @return static
	 */
	public static function wrap( MediaWikiServices $coreServices ) {
		return new static( $coreServices );
	}

	public function getEditorCapabilityFactory(): EditorCapabilityFactory {
		return $this->coreServices->getService( 'CommunityConfiguration.EditorCapabilityFactory' );
	}

	public function getConfigurationProviderFactory(): ConfigurationProviderFactory {
		return $this->coreServices->getService( 'CommunityConfiguration.ProviderFactory' );
	}

	public function getValidatorFactory(): ValidatorFactory {
		return $this->coreServices->getService( 'CommunityConfiguration.ValidatorFactory' );
	}

	public function getStoreFactory(): StoreFactory {
		return $this->coreServices->getService( 'CommunityConfiguration.StoreFactory' );
	}

	public function getWikiPageConfigReader(): WikiPageConfigReader {
		return $this->coreServices->getService( 'CommunityConfiguration.WikiPageConfigReader' );
	}

	public function getWikiPageStoreLoader(): Loader {
		return $this->coreServices->getService( 'CommunityConfiguration.WikiPageStore.Loader' );
	}

	public function getWikiPageStoreWriter(): Writer {
		return $this->coreServices->getService( 'CommunityConfiguration.WikiPageStore.Writer' );
	}
}
