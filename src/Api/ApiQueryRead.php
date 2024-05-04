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

		$this->getResult()->addValue( null, $this->getModuleName(), [
			'data' => $loadedConfig->getValue(),
		] );
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
			]
		];
	}
}
