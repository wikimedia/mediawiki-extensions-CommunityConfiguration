<?php

namespace MediaWiki\Extension\CommunityConfiguration\Hooks;

use MediaWiki\DomainEvent\DomainEventIngress;
use MediaWiki\Extension\CommunityConfiguration\Provider\ConfigurationProviderFactory;
use MediaWiki\Extension\CommunityConfiguration\Store\WikiPageStore;
use MediaWiki\Page\Event\PageDeletedEvent;
use MediaWiki\Page\Event\PageDeletedListener;
use MediaWiki\Page\Event\PageLatestRevisionChangedEvent;
use MediaWiki\Page\Event\PageLatestRevisionChangedListener;
use MediaWiki\Page\PageIdentity;

class WikiPageStoreEventIngress extends DomainEventIngress implements
	PageLatestRevisionChangedListener,
	PageDeletedListener
{
	private ConfigurationProviderFactory $factory;

	public function __construct( ConfigurationProviderFactory $factory ) {
		$this->factory = $factory;
	}

	private function invalidateForPage( PageIdentity $page ) {
		foreach ( $this->factory->getSupportedKeys() as $providerName ) {
			$provider = $this->factory->newProvider( $providerName );
			$store = $provider->getStore();
			if ( !$store instanceof WikiPageStore ) {
				// the subscriber only handles WikiPageStore-related actions
				continue;
			}

			if ( $page->isSamePageAs( $store->getConfigurationTitle() ) ) {
				$store->invalidate();
				return;
			}
		}
	}

	public function handlePageLatestRevisionChangedEvent(
		PageLatestRevisionChangedEvent $event
	): void {
		$this->invalidateForPage( $event->getPage() );
	}

	public function handlePageDeletedEvent( PageDeletedEvent $event ): void {
		$this->invalidateForPage( $event->getDeletedPage() );
	}

}
