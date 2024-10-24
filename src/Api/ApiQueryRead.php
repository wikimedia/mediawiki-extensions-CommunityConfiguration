<?php

namespace MediaWiki\Extension\CommunityConfiguration\Api;

use InvalidArgumentException;
use MediaWiki\Api\ApiQuery;
use MediaWiki\Api\ApiQueryBase;
use MediaWiki\Extension\CommunityConfiguration\Provider\ConfigurationProviderFactory;
use Psr\Log\LoggerInterface;
use Wikimedia\ParamValidator\ParamValidator;

class ApiQueryRead extends ApiQueryBase {

	private LoggerInterface $logger;
	private ConfigurationProviderFactory $configurationProviderFactory;

	public function __construct(
		ApiQuery $queryModule,
		string $moduleName,
		LoggerInterface $logger,
		ConfigurationProviderFactory $configurationProviderFactory
	) {
		parent::__construct( $queryModule, $moduleName, 'ccr' );

		$this->logger = $logger;
		$this->configurationProviderFactory = $configurationProviderFactory;
	}

	/**
	 * @inheritDoc
	 */
	public function execute() {
		$params = $this->extractRequestParams();
		try {
			$provider = $this->configurationProviderFactory->newProvider( $params['provider'] );
		} catch ( InvalidArgumentException $e ) {
			// NOTE: Assuming the list of supported keys in getAllowedParams() are correct, this
			// branch should be never triggered.

			$this->logger->error(
				__METHOD__ . ' failed to construct the {provider} provider',
				[ 'provider' => $params['provider'], 'exception' => $e ],
			);
			$this->dieWithException( $e );
		}

		$loadedConfig = $provider->loadValidConfiguration();
		if ( !$loadedConfig->isOK() ) {
			$this->dieStatus( $loadedConfig );
		}

		$version = $provider->getStore()->getVersion();
		// intentionally outside the $version if, because if the client wants a specific
		// version only, and no version data is available, then it's reasonable to treat that as
		// a version mismatch
		if ( $version !== $params['assertversion'] ) {
			$this->dieWithError( [
				'apierror-communityconfiguration-version-assertion-failure',
					$version,
					$params['assertversion'],
				]
			);
		}

		$result = [
			'data' => $loadedConfig->getValue(),
		];

		if ( $version ) {
			$result['version'] = $version;
		}

		$this->getResult()->addValue( null, $this->getModuleName(), $result );
	}

	/**
	 * @inheritDoc
	 */
	protected function getAllowedParams() {
		return [
			'provider' => [
				ParamValidator::PARAM_TYPE =>
					$this->configurationProviderFactory->getSupportedKeys(),
				ParamValidator::PARAM_REQUIRED => true,
			],
			'assertversion' => [
				ParamValidator::PARAM_TYPE => 'string',
			],
		];
	}
}
