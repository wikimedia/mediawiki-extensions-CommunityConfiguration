<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\CommunityConfiguration\CommunityConfigurationServices;
use MediaWiki\Extension\CommunityConfiguration\EditorCapabilities\IEditorCapability;
use MediaWiki\Title\Title;
use MediaWikiIntegrationTestCase;

/**
 * @covers \MediaWiki\Extension\CommunityConfiguration\EditorCapabilities\EditorCapabilityFactory
 */
class EditorCapabilityFactoryTest extends MediaWikiIntegrationTestCase {

	private function getSpecs() {
		return $this->getServiceContainer()->getMainConfig()->get( 'CommunityConfigurationEditorCapabilities' );
	}

	public function provideNewCapability() {
		foreach ( $this->getSpecs() as $key => $spec ) {
			yield [ $key ];
		}
	}

	public function testConstructCapability() {
		foreach ( $this->getSpecs() as $capabilityName => $_ ) {
			$this->assertInstanceOf(
				IEditorCapability::class,
				CommunityConfigurationServices::wrap( $this->getServiceContainer() )
					->getEditorCapabilityFactory()
					->newCapability(
						$capabilityName,
						RequestContext::getMain(),
						Title::newFromText( 'Testing' )
					)
			);
		}
	}
}
