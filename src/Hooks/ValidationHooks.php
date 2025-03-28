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

	public function __construct( private readonly ConfigurationProviderFactory $factory ) {
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
				$configForValidation = $content->getData();
				$versionStatus = WikiPageStore::readVersionField( $configForValidation->getValue() );
				if ( !$versionStatus->isOK() ) {
					// Without a usable version there is no schema to validate against; reject
					// the save rather than falling back to the most recent one.
					$status->merge( $versionStatus );
					continue;
				}
				$configForValidation = WikiPageStore::removeVersionDataFromStatus( $configForValidation )->getValue();
				$validator = $provider->getValidator();
				$result = $validator->validateStrictly(
					$configForValidation,
					$versionStatus->getValue()
				);
				$status->merge( $result );
			}
		}
	}
}
