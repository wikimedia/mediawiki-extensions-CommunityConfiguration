<?php

namespace MediaWiki\Extension\CommunityConfiguration\DomainEvents;

interface CommunityConfigurationChangedListener {

	public function handleCommunityConfigurationChangedEvent(
		CommunityConfigurationChangedEvent $event
	): void;
}
