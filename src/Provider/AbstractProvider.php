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

	/**
	 * Indicates whether this provider should be skipped on the dashboard.
	 *
	 * @var bool
	 */
	private bool $shouldSkipDashboard;

	/**
	 * Constructs a new instance of a provider.
	 *
	 * @param string $providerId
	 * @param bool $shouldSkipDashboard Whether the provider should be skipped on the dashboard.
	 * @param IConfigurationStore $store The store used by the provider.
	 * @param IValidator $validator The validator used by the provider.
	 */
	public function __construct(
		string $providerId,
		bool $shouldSkipDashboard,
		IConfigurationStore $store,
		IValidator $validator
	) {
		$this->providerId = $providerId;
		$this->store = $store;
		$this->validator = $validator;
		$this->shouldSkipDashboard = $shouldSkipDashboard;

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

	/**
	 * Determines whether the provider should be skipped in the dashboard listing.
	 *
	 * @return bool True if the provider should be skipped in the dashboard listing, false otherwise.
	 */
	public function shouldSkipDashboardListing(): bool {
		return $this->shouldSkipDashboard;
	}
}
