<?php

namespace MediaWiki\Extension\CommunityConfiguration\EditorCapabilities;

use MediaWiki\Extension\CommunityConfiguration\Provider\IConfigurationProvider;
use Psr\Log\LoggerAwareInterface;

interface IEditorCapability extends LoggerAwareInterface {

	/**
	 * Code that executes the capability
	 *
	 * This method should render whatever interface is needed to create the capability. It will
	 * be called from SpecialPage::execute, and it can assume it has complete control over the
	 * special page.
	 *
	 * @param IConfigurationProvider|null $provider Relevant provider, if applicable
	 * @param string|null $subpage Subpage (usable by the capability for eg. routing)
	 * @return void
	 */
	public function execute( ?IConfigurationProvider $provider, ?string $subpage = null ): void;
}
