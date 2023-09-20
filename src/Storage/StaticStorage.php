<?php

namespace MediaWiki\Extension\CommunityConfiguration\Storage;

class StaticStorage implements IConfigurationStore {

	public function loadConfigurationUncached(): array {
		return [
			'wgFooBar' => 42,
		];
	}
}
