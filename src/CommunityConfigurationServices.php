<?php

namespace MediaWiki\Extension\CommunityConfiguration;

use MediaWiki\Extension\CommunityConfiguration\Provider\ConfigurationProviderFactory;
use MediaWiki\Extension\CommunityConfiguration\Store\StoreFactory;
use MediaWiki\Extension\CommunityConfiguration\Validation\IValidator;
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

	public function getConfigurationProviderFactory(): ConfigurationProviderFactory {
		return $this->coreServices->getService( 'CommunityConfiguration.ProviderFactory' );
	}

	public function getValidatorFactory(): ValidatorFactory {
		return $this->coreServices->getService( 'CommunityConfiguration.ValidatorFactory' );
	}

	public function getStoreFactory(): StoreFactory {
		return $this->coreServices->getService( 'CommunityConfiguration.StoreFactory' );
	}
}