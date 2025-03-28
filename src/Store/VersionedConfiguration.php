<?php

namespace MediaWiki\Extension\CommunityConfiguration\Store;

/**
 * Configuration data paired with the schema version it was stored under
 *
 * Both come from a single read of the store. This pairing matters: the version decides
 * which schema the data is validated against and which migration chain converts it to the
 * latest version. Obtaining the two through separate calls (loadConfiguration() plus
 * getVersion(), say) can pair freshly read data with a stale version, which silently
 * validates the data against the wrong schema.
 *
 * Stores that do not track a schema version report null.
 */
class VersionedConfiguration {

	/**
	 * @param mixed $data The configuration, as returned by IConfigurationStore::loadConfiguration()
	 * @param string|null $version The schema version the data was stored under, if any
	 */
	public function __construct(
		private readonly mixed $data,
		private readonly ?string $version,
	) {
	}

	/**
	 * @return mixed The configuration, with any version metadata removed
	 */
	public function getData(): mixed {
		return $this->data;
	}

	public function getVersion(): ?string {
		return $this->version;
	}
}
