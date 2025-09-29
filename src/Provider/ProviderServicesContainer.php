<?php

namespace MediaWiki\Extension\CommunityConfiguration\Provider;

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

	// NOTE: No services are necessary now, this is here just to facilitate constructor signature
	// changes.
}
