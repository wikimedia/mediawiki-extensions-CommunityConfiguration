<?php

namespace MediaWiki\Extension\CommunityConfiguration\Provider;

use MediaWiki\Extension\CommunityConfiguration\Store\IConfigurationStore;
use MediaWiki\Extension\CommunityConfiguration\Validation\IValidator;
use MediaWiki\Message\Message;
use MediaWiki\Permissions\Authority;
use MessageLocalizer;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use StatusValue;

abstract class AbstractProvider implements IConfigurationProvider {
	use LoggerAwareTrait;

	private string $providerId;
	private IConfigurationStore $store;
	private IValidator $validator;
	private array $options;

	/**
	 * Constructs a new instance of a provider.
	 *
	 * @param string $providerId The unique identifier for the provider.
	 * @param array $options
	 * 		Configuration options for the provider, may be structured as follows:
	 * 		- 'skipDashboardListing' (bool, optional): Indicates whether this provider
	 * 			should be skipped on the dashboard.
	 * @param IConfigurationStore $store The store used by the provider.
	 * @param IValidator $validator The validator used by the provider.
	 */
	public function __construct(
		string $providerId,
		array $options,
		IConfigurationStore $store,
		IValidator $validator
	) {
		$this->providerId = $providerId;
		$this->store = $store;
		$this->validator = $validator;
		$this->options = $options;
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
	 * Store configuration, after possibly manipulating it
	 *
	 * Can be used by providers to manipulate $newConfig before letting it get saved.
	 *
	 * @param mixed $newConfig The configuration value to store. Can be any JSON serializable type
	 * @param Authority $authority
	 * @param string $summary
	 * @return StatusValue
	 */
	protected function storeConfiguration(
		$newConfig,
		Authority $authority,
		string $summary = ''
	): StatusValue {
		return $this->getStore()->storeConfiguration(
			$newConfig,
			$authority,
			$summary
		);
	}

	/**
	 * @inheritDoc
	 */
	public function getOptionValue( string $optionName ) {
		return $this->options[ $optionName ] ?? null;
	}
}
