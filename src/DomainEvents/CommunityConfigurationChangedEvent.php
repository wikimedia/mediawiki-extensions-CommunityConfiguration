<?php

namespace MediaWiki\Extension\CommunityConfiguration\DomainEvents;

use MediaWiki\DomainEvent\DomainEvent;
use MediaWiki\Extension\CommunityConfiguration\Provider\IConfigurationProvider;

class CommunityConfigurationChangedEvent extends DomainEvent {

	public const TYPE = 'CommunityConfigurationChanged';

	public function __construct(
		private readonly IConfigurationProvider $provider
	) {
		parent::__construct();
		$this->declareEventType( self::TYPE );
	}

	/**
	 * Get the relevant configuration provider
	 */
	public function getProvider(): IConfigurationProvider {
		return $this->provider;
	}
}
