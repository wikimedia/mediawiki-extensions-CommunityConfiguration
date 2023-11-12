<?php

namespace MediaWiki\Extension\CommunityConfiguration\Hooks;

use JsonContent;
use MediaWiki\Content\Hook\JsonValidateSaveHook;
use MediaWiki\Extension\CommunityConfiguration\Provider\ConfigurationProviderFactory;
use MediaWiki\Extension\CommunityConfiguration\Store\WikiPageStore;
use MediaWiki\Page\PageIdentity;
use StatusValue;

class ValidationHooks implements JsonValidateSaveHook {

	private ConfigurationProviderFactory $factory;

	public function __construct( ConfigurationProviderFactory $factory ) {
		$this->factory = $factory;
	}

	/**
	 * @inheritDoc
	 */
	public function onJsonValidateSave(
		JsonContent $content,
		PageIdentity $pageIdentity,
		StatusValue $status
	) {
		// FIXME avoid constructing providers, index of wiki configs?
		foreach ( $this->factory->getSupportedKeys() as $providerName ) {
			$provider = $this->factory->newProvider( $providerName );
			$store = $provider->getStore();
			if ( !$store instanceof WikiPageStore ) {
				// does not make sense to do any validation here
				continue;
			}

			// REVIEW: Calling equals() does not seem to work. Why?
			if ( $store->getConfigurationTitle()->getId() === $pageIdentity->getId() ) {
				$validator = $provider->getValidator();
				$result = $validator->validate( (array)$content->getData()->getValue() );
				$status->merge( $result );
			}
		}
	}
}
