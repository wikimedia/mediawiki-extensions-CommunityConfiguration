<?php

namespace MediaWiki\Extension\CommunityConfiguration\Hooks;

use MediaWiki\ChangeTags\Hook\ChangeTagsListActiveHook;
use MediaWiki\ChangeTags\Hook\ListDefinedTagsHook;
use MediaWiki\Extension\CommunityConfiguration\Store\WikiPageStore;

class ChangeTagHooks implements ListDefinedTagsHook, ChangeTagsListActiveHook {

	/**
	 * @inheritDoc
	 */
	public function onListDefinedTags( &$tags ): void {
		$tags[] = WikiPageStore::TAG_NAME;
	}

	/**
	 * @inheritDoc
	 */
	public function onChangeTagsListActive( &$tags ): void {
		$tags[] = WikiPageStore::TAG_NAME;
	}
}
