<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\CommunityConfiguration\CommunityConfigurationServices;
use MediaWiki\Title\Title;
use MediaWikiIntegrationTestCase;

/**
 * @covers \MediaWiki\Extension\CommunityConfiguration\EditorCapabilities\EditorCapabilityFactory
 */
class EditorCapabilityFactoryTest extends MediaWikiIntegrationTestCase {

	public function testConstructCapability() {
		$serviceContainer = $this->getServiceContainer();
		$specs = $serviceContainer->getMainConfig()->get( 'CommunityConfigurationEditorCapabilities' );
		$factory = CommunityConfigurationServices::wrap( $serviceContainer )
			->getEditorCapabilityFactory();
		$requestContext = RequestContext::getMain();
		$title = Title::makeTitle( NS_MAIN, 'Testing' );

		foreach ( $specs as $capabilityName => $_ ) {
			$factory->newCapability(
				$capabilityName,
				$requestContext,
				$title
			);
		}
		$this->addToAssertionCount( 1 );
	}
}
