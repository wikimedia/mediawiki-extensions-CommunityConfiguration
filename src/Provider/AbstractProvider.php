<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\CommunityConfiguration\Provider;

use MediaWiki\Extension\CommunityConfiguration\CommunityConfigurationServices;
use MediaWiki\Extension\CommunityConfiguration\Store\IConfigurationStore;
use MediaWiki\Extension\CommunityConfiguration\Validation\IValidator;
use MediaWiki\MediaWikiServices;
use MediaWiki\Message\Message;
use MediaWiki\Permissions\Authority;
use MessageLocalizer;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use StatusValue;

abstract class AbstractProvider implements IConfigurationProvider {
	use LoggerAwareTrait;

	private ProviderServicesContainer $providerServicesContainer;
	private string $providerId;
	private IConfigurationStore $store;
	private IValidator $validator;
	private array $options;

	/**
	 * Constructs a new instance of a provider.
	 *
	 * @param ProviderServicesContainer|string $providerServicesContainerOrProviderId
	 * @param string|array $providerIdOrOptions The unique identifier for the provider.
	 * @param array|IConfigurationStore $optionsOrStore
	 * 		Configuration options for the provider, may be structured as follows:
	 * 		- 'excludeFromUI' (bool, optional): Indicates whether this provider
	 * 			should be skipped on the dashboard.
	 * 		- 'helpPage' (string, optional): Title of the help page of the feature on mediawiki.org.
	 * 			e.g., Help:Growth/Mentorship'
	 * 		- 'helpURL' (string, optional): Full URL to a help resource.
	 * 			'helpURL' should be used if the help content is on a specific section of a page or is an external help
	 * 			resource, e.g., 'https://www.mediawiki.org/wiki/Special:MyLanguage/Page#Section'. 'helpURL' should be a
	 * 			full URL pointing to that section with an anchor.
	 * 		- At most one of helpURL and helpPage should be provided, not both.
	 * @param IConfigurationStore|IValidator $storeOrValidator The store used by the provider.
	 * @param IValidator|null $validator The validator used by the provider.
	 *
	 * @note For B/C purposes, the first one argument can be skipped (starting with $providerId)
	 * instead.
	 */
	public function __construct(
		ProviderServicesContainer|string $providerServicesContainerOrProviderId,
		string|array $providerIdOrOptions,
		array|IConfigurationStore $optionsOrStore,
		IConfigurationStore|IValidator $storeOrValidator,
		?IValidator $validator = null
	) {
		if ( $providerServicesContainerOrProviderId instanceof ProviderServicesContainer ) {
			// new signature
			$this->providerServicesContainer = $providerServicesContainerOrProviderId;
			$this->providerId = $providerIdOrOptions;
			$this->options = $optionsOrStore;
			$this->store = $storeOrValidator;
			$this->validator = $validator;
		} else {
			// old signature, get the services container from the global state
			// TODO: Remove this branch
			$this->providerServicesContainer = CommunityConfigurationServices::wrap( MediaWikiServices::getInstance() )
				->getProviderServicesContainer();
			$this->providerId = $providerServicesContainerOrProviderId;
			$this->options = $providerIdOrOptions;
			$this->store = $optionsOrStore;
			$this->validator = $storeOrValidator;
		}
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
	 * Store configuration
	 *
	 * Providers should override this if they want to modify how _both_ storeValidConfiguration()
	 * and alwaysStoreValidConfiguration() behave.
	 *
	 * @param mixed $newConfig The configuration value to store. Can be any JSON serializable type
	 * @param Authority $authority
	 * @param string $summary
	 * @param bool $bypassPermissionCheck Whether IConfigurationStore::alwaysStoreConfiguration
	 * should be used.
	 * @return StatusValue
	 */
	private function doStoreValidConfiguration(
		$newConfig,
		Authority $authority,
		string $summary,
		bool $bypassPermissionCheck
	): StatusValue {
		$validationStatus = $this->getValidator()->validateStrictly( $newConfig );
		if ( !$validationStatus->isGood() ) {
			return $validationStatus;
		}

		$args = [
			$newConfig,
			$this->getValidator()->areSchemasSupported()
				? $this->getValidator()->getSchemaVersion()
				: null,
			$authority,
			$summary,
		];

		if ( $bypassPermissionCheck ) {
			return $this->getStore()->alwaysStoreConfiguration( ...$args );
		} else {
			return $this->getStore()->storeConfiguration( ...$args );
		}
	}

	/**
	 * @inheritDoc
	 */
	public function storeValidConfiguration(
		$newConfig,
		Authority $authority,
		string $summary = ''
	): StatusValue {
		return $this->doStoreValidConfiguration( $newConfig, $authority, $summary, false );
	}

	/**
	 * @inheritDoc
	 */
	public function alwaysStoreValidConfiguration(
		$newConfig,
		Authority $authority,
		string $summary = ''
	): StatusValue {
		return $this->doStoreValidConfiguration( $newConfig, $authority, $summary, true );
	}

	/**
	 * @inheritDoc
	 */
	public function getOptionValue( string $optionName ) {
		return $this->options[ $optionName ] ?? null;
	}
}
