<?php

namespace MediaWiki\Extension\CommunityConfiguration\Specials;

use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\SpecialPage\SpecialPageFactory;

class SpecialCommunityConfiguration extends SpecialPage {

	private ?SpecialPage $dashboardSpecial;

	public function __construct(
		SpecialPageFactory $specialPageFactory
	) {
		parent::__construct( 'CommunityConfiguration' );
		$this->dashboardSpecial = $specialPageFactory->getPage( 'CommunityConfigurationDashboard' );
	}

	/**
	 * @param string|null $subPage
	 */
	public function execute( $subPage ) {
		if ( !$subPage ) {
			$this->dashboardSpecial->execute( $subPage );
		}
		// TODO: check provider exists and display editor
	}
}
