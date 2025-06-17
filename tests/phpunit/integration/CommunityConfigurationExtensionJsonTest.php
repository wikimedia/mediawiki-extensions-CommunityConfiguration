<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use MediaWiki\Tests\ExtensionJsonTestBase;

/**
 * @coversNothing
 */
class CommunityConfigurationExtensionJsonTest extends ExtensionJsonTestBase {
	/** @inheritDoc */
	protected static string $extensionJsonPath = __DIR__ . '/../../../extension.json';

	/** @inheritDoc */
	protected ?string $serviceNamePrefix = 'CommunityConfiguration';

	/** @inheritDoc */
	protected static bool $requireHookHandlers = true;
}
