<?php

namespace MediaWiki\Extension\CommunityConfiguration\Api;

use InvalidArgumentException;
use MediaWiki\Api\ApiBase;
use MediaWiki\Api\ApiMain;
use MediaWiki\Extension\CommunityConfiguration\Provider\ConfigurationProviderFactory;
use MediaWiki\Extension\CommunityConfiguration\Validation\ValidationStatus;
use MediaWiki\Json\FormatJson;
use MediaWiki\Logger\LoggerFactory;
use Psr\Log\LoggerAwareTrait;
use Wikimedia\ParamValidator\ParamValidator;

class ApiEdit extends ApiBase {
	use LoggerAwareTrait;

	private ConfigurationProviderFactory $configurationProviderFactory;

	public function __construct(
		ApiMain $mainModule,
		string $moduleName,
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

		$newConfigStatus = FormatJson::parse( $params['content'] );
		if ( !$newConfigStatus->isOK() ) {
			$this->dieStatus( $newConfigStatus );
		}

		$status = $provider->storeValidConfiguration(
			$newConfigStatus->getValue(),
			$this->getAuthority(),
			$params['summary']
		);

		if ( !$status->isOK() ) {
			if ( $status instanceof ValidationStatus ) {
				$errorData = $status->getValidationErrorsData();
				$lastIndex = count( $errorData ) - 1;
				foreach ( $status->getMessages() as $index => $errorMessageSpecifier ) {
					if ( $index === $lastIndex ) {
						$this->dieWithError(
							$errorMessageSpecifier,
							null,
							$errorData[$index]
						);
					}
					$this->addError(
						$errorMessageSpecifier,
						null,
						$errorData[$index]
					);
				}
			}
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
