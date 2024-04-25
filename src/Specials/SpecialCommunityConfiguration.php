<?php

namespace MediaWiki\Extension\CommunityConfiguration\Specials;

use InvalidArgumentException;
use LogicException;
use MediaWiki\Extension\CommunityConfiguration\Provider\ConfigurationProviderFactory;
use MediaWiki\Html\Html;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\Output\OutputPage;
use MediaWiki\SpecialPage\SpecialPage;
use Wikimedia\ObjectFactory\ObjectFactory;

class SpecialCommunityConfiguration extends SpecialPage {

	private ObjectFactory $objectFactory;
	private ConfigurationProviderFactory $providerFactory;

	private const CAPABILITY_DASHBOARD = 'dashboard';
	private const CAPABILITY_EDITOR = 'editor';
	private const CAPABILITIES = [
		self::CAPABILITY_DASHBOARD => [
			'class' => DashboardEditorCapability::class,
			'services' => [
				'CommunityConfiguration.ProviderFactory',
			]
		],
		self::CAPABILITY_EDITOR => GenericFormEditorCapability::SPEC,
	];

	public function __construct(
		ObjectFactory $objectFactory,
		ConfigurationProviderFactory $providerFactory
	) {
		parent::__construct( 'CommunityConfiguration' );
		$this->objectFactory = $objectFactory;
		$this->providerFactory = $providerFactory;
	}

	private function getEditorCapabilityByName( string $name ): AbstractEditorCapability {
		if ( !array_key_exists( $name, self::CAPABILITIES ) ) {
			throw new InvalidArgumentException( 'Capability ' . $name . ' is not supported' );
		}

		// ObjectFactory::createObject supports arrays as its arguments, not just callables
		// Link to Phan bug: https://github.com/phan/phan/issues/1648
		// @phan-suppress-next-line PhanTypeInvalidCallableArrayKey
		$result = $this->objectFactory->createObject(
			self::CAPABILITIES[$name],
			[
				'assertClass' => AbstractEditorCapability::class,
				'extraArgs' => [ $this->getContext(), $this->getPageTitle() ],
			]
		);

		// NOTE: This is here to allow for type hints.
		if ( !$result instanceof AbstractEditorCapability ) {
			throw new LogicException( 'ObjectFactory\'s assertion is invalid' );
		}

		$result->setLogger( LoggerFactory::getInstance( 'CommunityConfiguration' ) );
		return $result;
	}

	/**
	 * @param string|null $subPage
	 * @return void
	 */
	public function execute( $subPage ) {
		parent::execute( $subPage );
		$out = $this->getContext()->getOutput();

		if ( $subPage === null ) {
			$capabilityName = self::CAPABILITY_DASHBOARD;
		} else {
			if ( !$this->isProviderSupported( $subPage ) ) {
				$this->showErrorMessage( $out, 'communityconfiguration-provider-not-found', $subPage );
				return;
			}
			// If not displayed on the dashboard, it doesn't necessarily mean it's not supported.
			if ( !$this->shouldBeDisplayedOnDashboard( $subPage ) ) {
				$this->showErrorMessage( $out, 'communityconfiguration-provider-not-found', $subPage );
				return;
			}

			// TODO: Make it possible to use a different capability, which is specific to
			// the needs of a particular client extension.
			$capabilityName = self::CAPABILITY_EDITOR;
		}

		$this->getEditorCapabilityByName( $capabilityName )->execute( $subPage );
	}

	/**
	 * Show an error message on the output page
	 *
	 * @param OutputPage $out
	 * @param string $messageKey
	 * @param string $subPage
	 * @return void
	 */
	private function showErrorMessage( OutputPage $out, string $messageKey, $subPage ) {
		$out->addHTML( Html::rawElement( 'p', [ 'class' => 'error' ], $this->msg(
			$messageKey,
			$subPage
		)->parse() ) );
	}

	/**
	 * @param string $providerName The name of the provider as registered in extension.json
	 * @return bool
	 */
	private function isProviderSupported( string $providerName ): bool {
		return in_array( $providerName, $this->providerFactory->getSupportedKeys() );
	}

	/**
	 * Determines if a provider should be displayed on the dashboard.
	 *
	 * @param string $providerName The name of the provider
	 * @return bool
	 */
	private function shouldBeDisplayedOnDashboard( string $providerName ): bool {
		$provider = $this->providerFactory->newProvider( $providerName );
		return !$provider->shouldSkipDashboardListing();
	}

}
