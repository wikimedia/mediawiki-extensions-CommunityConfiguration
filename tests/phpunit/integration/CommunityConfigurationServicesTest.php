<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use MediaWiki\Extension\CommunityConfiguration\CommunityConfigurationServices;
use MediaWiki\Tests\ExtensionServicesTestBase;

/**
 * @covers \MediaWiki\Extension\CommunityConfiguration\CommunityConfigurationServices
 */
class CommunityConfigurationServicesTest extends ExtensionServicesTestBase {

	/** @inheritDoc */
	protected static string $className = CommunityConfigurationServices::class;

	/** @inheritDoc */
	protected string $serviceNamePrefix = 'CommunityConfiguration.';

	/** @inheritDoc */
	protected static bool $wrapsServiceContainer = true;

	/** @inheritDoc */
	protected array $serviceNamesWithoutMethods = [
		// B&C compatibility service name
		'CommunityConfiguration.ProviderFactory',
	];
}
