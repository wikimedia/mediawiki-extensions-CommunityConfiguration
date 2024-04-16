<?php

namespace MediaWiki\Extension\CommunityConfiguration\Provider;

use MediaWiki\Extension\CommunityConfiguration\Store\IConfigurationStore;
use MediaWiki\Extension\CommunityConfiguration\Validation\IValidator;
use MediaWiki\Message\Message;
use MessageLocalizer;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

abstract class AbstractProvider implements IConfigurationProvider {
	use LoggerAwareTrait;

	private string $providerId;
	private IConfigurationStore $store;
	private IValidator $validator;

	public function __construct(
		string $providerId,
		IConfigurationStore $store,
		IValidator $validator
	) {
		$this->providerId = $providerId;
		$this->store = $store;
		$this->validator = $validator;

		$this->setLogger( new NullLogger() );
	}

	/**
	 * @inheritDoc
	 */
	public function getId(): string {
		return $this->providerId;
	}

	/**
	 * @param MessageLocalizer $localizer
	 * @inheritDoc
	 */
	public function getName( MessageLocalizer $localizer ): Message {
		return $localizer->msg(
			sprintf( 'communityconfiguration-%s-title', strtolower( $this->getId() ) )
		);
	}

	/**
	 * @inheritDoc
	 */
	public function getStore(): IConfigurationStore {
		return $this->store;
	}

	/**
	 * @inheritDoc
	 */
	public function getValidator(): IValidator {
		return $this->validator;
	}
}
