<?php

namespace MediaWiki\Extension\CommunityConfiguration\EditorCapabilities;

use Psr\Log\LoggerAwareInterface;

interface IEditorCapability extends LoggerAwareInterface {

	/**
	 * Code that executes the capability
	 *
	 * This method should render whatever interface is needed to create the capability. It will
	 * be called from SpecialPage::execute, and it can assume it has complete control over the
	 * special page.
	 *
	 * @param string|null $subpage
	 * @return void
	 */
	public function execute( ?string $subpage ): void;
}
