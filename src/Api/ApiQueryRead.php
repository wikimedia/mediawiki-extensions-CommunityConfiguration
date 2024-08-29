<?php

namespace MediaWiki\Extension\CommunityConfiguration\Api;

use ApiQuery;
use ApiQueryBase;
use InvalidArgumentException;
use MediaWiki\Extension\CommunityConfiguration\Provider\ConfigurationProviderFactory;
use MediaWiki\Logger\LoggerFactory;
use Psr\Log\LoggerAwareTrait;
use Wikimedia\ParamValidator\ParamValidator;

class ApiQueryRead extends ApiQueryBase {
	use LoggerAwareTrait;

	private ConfigurationProviderFactory $configurationProviderFactory;

	/**
	 * @param ApiQuery $queryModule
	 * @param string $moduleName
	 * @param ConfigurationProviderFactory $configurationProviderFactory
	 */
	public function __construct(
		ApiQuery $queryModule,
		$moduleName,
		ConfigurationProviderFactory $configurationProviderFactory
	) {
		parent::__construct( $queryModule, $moduleName, 'ccr' );
		$this->setLogger( LoggerFactory::getInstance( 'CommunityConfiguration' ) );

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
