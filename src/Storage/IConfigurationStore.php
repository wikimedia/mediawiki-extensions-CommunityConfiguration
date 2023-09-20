<?php

namespace MediaWiki\Extension\CommunityConfiguration\Storage;

interface IConfigurationStore {

	public function loadConfigurationUncached(): array;
}
