<?php

namespace MediaWiki\Extension\CommunityConfiguration;

use ExtensionRegistry;
use MediaWiki\Config\Config;

class Utils {

	/**
	 * Merge $key from config and attributes
	 *
	 * @param Config $config
	 * @param string $key
	 * @return array
	 */
	public static function getMergedAttribute( Config $config, string $key ) {
		return array_merge(
		// NOTE: We want to give precedence to the Config version. Considering array_merge
		// gives precedence to the _last_ array, the data rom Config needs to come second.
			ExtensionRegistry::getInstance()
				->getAttribute( $key ),
			$config->get( $key )
		);
	}
}
