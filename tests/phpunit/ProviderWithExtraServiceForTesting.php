<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use MediaWiki\Extension\CommunityConfiguration\Provider\DataProvider;
use MediaWiki\Extension\CommunityConfiguration\Provider\ProviderServicesContainer;
use MediaWiki\Extension\CommunityConfiguration\Store\IConfigurationStore;
use MediaWiki\Extension\CommunityConfiguration\Validation\IValidator;
use MediaWiki\Utils\UrlUtils;

class ProviderWithExtraServiceForTesting extends DataProvider {

	public function __construct(
		ProviderServicesContainer $providerServicesContainer,
		string $providerId,
		array $options,
		IConfigurationStore $store,
		IValidator $validator,
		// to be able to assert $extraService is provided
		UrlUtils $extraService
	) {
		parent::__construct( $providerServicesContainer, $providerId, $options, $store, $validator );
	}
}
