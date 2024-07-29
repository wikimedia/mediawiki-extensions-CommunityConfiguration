<?php

namespace MediaWiki\Extension\CommunityConfiguration\Schema;

use stdClass;

/**
 * Class capable of converting data to conform to a particular schema version (using either data
 * confirming to the immediately preceding schema version, or to the immediately succeeding
 * schema version).
 *
 * This can be used to write schema migration, and to reflect schema changes in the
 * CommunityConfiguration-managed data.
 *
 * In the future, CommunityConfiguration should be capable of (partially) autogenerating migration
 * classes (implementing this interface), but this is not yet the case.
 */
interface ISchemaConverter {

	/**
	 * Upgrade data using older data
	 *
	 * @param stdClass $data Data conforming to the immediately preceding schema version
	 * @return stdClass Result of conversion
	 */
	public function upgradeFromOlder( stdClass $data ): stdClass;

	/**
	 * Downgrade data to previous version using current data
	 *
	 * @param stdClass $data Data conforming to the current schema version
	 * @return stdClass Result of conversion, data conforming to the previous schema version
	 */
	public function downgradeToPrevious( stdClass $data ): stdClass;
}
