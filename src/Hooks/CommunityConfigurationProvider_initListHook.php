<?php
namespace MediaWiki\Extension\CommunityConfiguration\Hooks;

// phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

/**
 * This is a hook handler interface, see docs/Hooks.md in core.
 * Use the hook name "CommunityConfigurationProvider_initListHook" to register handlers implementing this interface.
 *
 * @stable to implement
 * @ingroup Hooks
 */
interface CommunityConfigurationProvider_initListHook {
	/**
	 * @param array &$providers List of registered providers, mapping of provider name to provider specification.
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onCommunityConfigurationProvider_initList(
		array &$providers
	);
}
