<?php

namespace MediaWiki\Extension\CommunityConfiguration\Provider;

use MediaWiki\DomainEvent\DomainEventDispatcher;
use Wikimedia\Rdbms\IConnectionProvider;

/**
 * A service locator to be used by AbstractProvider implementations
 *
 * Signature changes of AbstractProvider's constructor are costly, as they
 * need to be done in all implementations, including those in extensions.
 * Thanks to the service locator, AbstractProvider can only accept a single service,
 * dynamically loading whatever it needs from it.
 *
 * This is better than injecting MediaWikiServices, as it does not allow arbitrary creation
 * of services.
 */
class ProviderServicesContainer {

	public function __construct(
		private readonly IConnectionProvider $connectionProvider,
		private readonly DomainEventDispatcher $domainEventDispatcher
	) {
	}

	public function getConnectionProvider(): IConnectionProvider {
		return $this->connectionProvider;
	}

	public function getDomainEventDispatcher(): DomainEventDispatcher {
		return $this->domainEventDispatcher;
	}
}
