<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\CommunityConfiguration\Hooks;

use MediaWiki\Content\Hook\JsonValidateSaveHook;
use MediaWiki\Content\JsonContent;
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
	): void {
		foreach ( $this->factory->getSupportedKeys() as $providerName ) {
			$provider = $this->factory->newProvider( $providerName );
			$store = $provider->getStore();
			if ( !$store instanceof WikiPageStore ) {
				// does not make sense to do any validation here
				continue;
			}

			if ( $pageIdentity->isSamePageAs( $store->getConfigurationTitle() ) ) {
				$validator = $provider->getValidator();
				$result = $validator->validateStrictly(
					WikiPageStore::removeVersionDataFromStatus( $content->getData() )->getValue()
				);
				$status->merge( $result );
			}
		}
	}
}
