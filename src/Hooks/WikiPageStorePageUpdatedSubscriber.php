<?php

namespace MediaWiki\Extension\CommunityConfiguration\Hooks;

use MediaWiki\DomainEvent\EventSubscriberBase;
use MediaWiki\Extension\CommunityConfiguration\Provider\ConfigurationProviderFactory;
use MediaWiki\Extension\CommunityConfiguration\Store\WikiPageStore;
use MediaWiki\Storage\PageUpdatedEvent;

class WikiPageStorePageUpdatedSubscriber extends EventSubscriberBase {

	private ConfigurationProviderFactory $factory;

	public function __construct( ConfigurationProviderFactory $factory ) {
		$this->factory = $factory;
	}

	public function handlePageUpdatedEventAfterCommit( PageUpdatedEvent $event ): void {
		foreach ( $this->factory->getSupportedKeys() as $providerName ) {
			$provider = $this->factory->newProvider( $providerName );
			$store = $provider->getStore();
			if ( !$store instanceof WikiPageStore ) {
				// the subscriber only handles WikiPageStore-related actions
				continue;
			}

			if ( $event->getPage()->isSamePageAs( $store->getConfigurationTitle() ) ) {
				$store->invalidate();
				return;
			}
		}
	}
}
