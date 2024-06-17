<?php

namespace MediaWiki\Extension\CommunityConfiguration\Hooks;

use MediaWiki\Extension\CommunityConfiguration\Provider\IConfigurationProvider;
use MediaWiki\HookContainer\HookContainer;

/**
 * This is a hook runner class, see docs/Hooks.md in core.
 *
 * @internal
 */
class HookRunner implements CommunityConfigurationSchemaBeforeEditorHook {

	private HookContainer $hookContainer;

	public function __construct( HookContainer $hookContainer ) {
		$this->hookContainer = $hookContainer;
	}

	/**
	 * @inheritDoc
	 */
	public function onCommunityConfigurationSchemaBeforeEditor(
		IConfigurationProvider $provider,
		array &$rootSchema
	) {
		return $this->hookContainer->run(
			'CommunityConfigurationSchemaBeforeEditor',
			[ $provider, &$rootSchema ]
		);
	}
}
