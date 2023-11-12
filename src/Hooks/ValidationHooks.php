<?php

namespace MediaWiki\Extension\CommunityConfiguration\Hooks;

use JsonContent;
use MediaWiki\Content\Hook\JsonValidateSaveHook;
use MediaWiki\Extension\CommunityConfiguration\Provider\ConfigurationProviderFactory;
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
		// TODO Not all providers necessarily have to be on-wiki pages
		foreach ( $this->factory->getSupportedKeys() as $providerName ) {
			$provider = $this->factory->newProvider( $providerName );
			if ( strpos( $provider->getStore()->getConfigurationLocation(), $pageIdentity->getDBkey() ) ) {
				$validator = $provider->getValidator();
				$result = $validator->validate( (array)$content->getData()->getValue() );
				$status->merge( $result );
			}
		}
	}
}
