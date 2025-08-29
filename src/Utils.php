<?php

namespace MediaWiki\Extension\CommunityConfiguration;

use MediaWiki\Config\Config;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Registration\ExtensionRegistry;

class Utils {

	/**
	 * Merge $key from config and attributes
	 *
	 * @param Config|ServiceOptions $config
	 * @param ExtensionRegistry $extensionRegistry
	 * @param string $key
	 * @return array
	 */
	public static function getMergedAttribute(
		Config|ServiceOptions $config,
		ExtensionRegistry $extensionRegistry,
		string $key
	) {
		return array_merge(
		// NOTE: We want to give precedence to the Config version. Considering array_merge
		// gives precedence to the _last_ array, the data from Config needs to come second.
			$extensionRegistry->getAttribute( $key ),
			$config->get( $key )
		);
	}
}
