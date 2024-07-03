<?php

namespace MediaWiki\Extension\CommunityConfiguration\Hooks;

use MediaWiki\Extension\CommunityConfiguration\Provider\IConfigurationProvider;
use MediaWiki\HookContainer\HookContainer;

/**
 * This is a hook runner class, see docs/Hooks.md in core.
 *
 * @internal
 */
class HookRunner implements
	CommunityConfigurationSchemaBeforeEditorHook,
	CommunityConfigurationProvider_initListHook
{

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

	/**
	 * @inheritDoc
	 */
	public function onCommunityConfigurationProvider_initList( array &$providers ) {
		return $this->hookContainer->run(
			'CommunityConfigurationProvider_initList',
			[ &$providers ]
		);
	}
}
