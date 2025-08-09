<?php

namespace MediaWiki\Extension\CommunityConfiguration\Hooks;

use MediaWiki\DomainEvent\DomainEventIngress;
use MediaWiki\Extension\CommunityConfiguration\Provider\ConfigurationProviderFactory;
use MediaWiki\Extension\CommunityConfiguration\Store\WikiPageStore;
use MediaWiki\Page\Event\PageRevisionUpdatedEvent;
use MediaWiki\Page\Event\PageRevisionUpdatedListener;

class WikiPageStoreEventIngress extends DomainEventIngress implements PageRevisionUpdatedListener {

	private ConfigurationProviderFactory $factory;

	public function __construct( ConfigurationProviderFactory $factory ) {
		$this->factory = $factory;
	}

	public function handlePageRevisionUpdatedEvent( PageRevisionUpdatedEvent $event ): void {
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
