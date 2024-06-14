<?php

namespace MediaWiki\Extension\CommunityConfiguration\Hooks;

use MediaWiki\Extension\CommunityConfiguration\Provider\IConfigurationProvider;

/**
 * This is a hook handler interface, see docs/Hooks.md in core.
 * Use the hook name "CommunityConfigurationSchemaBeforeEditor" to register handlers implementing this interface.
 *
 * @stable to implement
 * @ingroup Hooks
 */
interface CommunityConfigurationSchemaBeforeEditorHook {

	/**
	 * @param IConfigurationProvider $provider
	 * @param array &$rootSchema
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onCommunityConfigurationSchemaBeforeEditor(
		IConfigurationProvider $provider,
		array &$rootSchema
	);
}
