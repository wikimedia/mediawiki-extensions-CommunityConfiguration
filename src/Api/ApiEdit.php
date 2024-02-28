<?php

namespace MediaWiki\Extension\CommunityConfiguration\Api;

use ApiBase;
use ApiMain;
use FormatJson;
use InvalidArgumentException;
use MediaWiki\Extension\CommunityConfiguration\Provider\ConfigurationProviderFactory;
use MediaWiki\Logger\LoggerFactory;
use Psr\Log\LoggerAwareTrait;
use Wikimedia\ParamValidator\ParamValidator;

class ApiEdit extends ApiBase {
	use LoggerAwareTrait;

	private ConfigurationProviderFactory $configurationProviderFactory;

	/**
	 * @param ApiMain $mainModule
	 * @param string $moduleName
	 * @param ConfigurationProviderFactory $configurationProviderFactory
	 */
	public function __construct(
		ApiMain $mainModule,
		$moduleName,
		ConfigurationProviderFactory $configurationProviderFactory
	) {
		parent::__construct( $mainModule, $moduleName );
		$this->setLogger( LoggerFactory::getInstance( 'CommunityConfiguration' ) );

		$this->configurationProviderFactory = $configurationProviderFactory;
	}

	/**
	 * @inheritDoc
	 */
	public function needsToken() {
		return 'csrf';
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

		$newConfigStatus = FormatJson::parse( $params['content'], FormatJson::FORCE_ASSOC );
		if ( !$newConfigStatus->isOK() ) {
			$this->dieStatus( $newConfigStatus );
		}

		$status = $provider->storeValidConfiguration(
			$newConfigStatus->getValue(),
			$this->getAuthority(),
			$params['summary']
		);
		if ( !$status->isOK() ) {
			$this->dieStatus( $status );
		}

		$this->getResult()->addValue( null, $this->getModuleName(), [
			'result' => 'success',
		] );
	}

	/**
	 * @inheritDoc
	 */
	protected function getAllowedParams() {
		return [
			'provider' => [
				ParamValidator::PARAM_TYPE => $this->configurationProviderFactory->getSupportedKeys(),
				ParamValidator::PARAM_REQUIRED => true,
			],
			'content' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			],
			'summary' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_DEFAULT => '',
			],
		];
	}
}
