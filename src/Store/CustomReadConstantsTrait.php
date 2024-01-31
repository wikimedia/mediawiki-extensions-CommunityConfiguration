<?php

namespace MediaWiki\Extension\CommunityConfiguration\Store;

trait CustomReadConstantsTrait {
	/**
	 * @param int $flags Bitfield consisting of READ_* constants
	 * @return int Bitfield consisting only of standard IDBAccessObject READ_* constants.
	 */
	private function removeCustomFlags( int $flags ): int {
		return $flags & ~ICustomReadConstants::READ_UNCACHED;
	}
}
