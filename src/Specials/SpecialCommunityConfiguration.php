<?php
namespace MediaWiki\Extension\CommunityConfiguration\Specials;

use LogicException;
use MediaWiki\Extension\CommunityConfiguration\Provider\ConfigurationProviderFactory;
use MediaWiki\Extension\CommunityConfiguration\Provider\WikiPageConfigProvider;
use MediaWiki\Html\Html;
use MediaWiki\SpecialPage\FormSpecialPage;
use MediaWiki\Status\Status;
use PermissionsError;


class SpecialCommunityConfiguration extends FormSpecialPage {
	/** @var string Right required to write */
	public const REQUIRED_RIGHT_TO_WRITE = 'editinterface';

	private ConfigurationProviderFactory $providerFactory;
	private ?WikiPageConfigProvider $provider = null;


	public function __construct(
		ConfigurationProviderFactory $providerFactory
	) {
		parent::__construct( 'CommunityConfiguration' );
		$this->providerFactory = $providerFactory;
	}

	private function exists( string $providerName ): bool {
		return in_array( $providerName, $this->providerFactory->getSupportedKeys() );
	}

	private function setProvider( string $name ) {
		$provider = $this->providerFactory->newProvider( $name );
		if ( !$provider instanceof WikiPageConfigProvider ) {
			throw new LogicException( 'Expected ' . $name . ' to be a WikiPageConfigProvider' );
		}
		$this->provider = $provider;
	}

	public function execute( $par ) {
		$out = $this->getContext()->getOutput();
		if ( $par && $this->exists( $par ) ){
			$this->setProvider( $par );
		} else if ( $par ) {
			$out->addHTML( Html::element( 'p', [ 'class' => 'error' ], $this->msg(
				'communityconfiguration-provider-not-found',
				$par
			)->parse() ) );
		} else {
			$availableProviders = [];
			foreach ( $this->providerFactory->getSupportedKeys() as $key ) {
				$availableProviders[] = Html::rawElement(
					'li',
					[],
					Html::element(
						'a',
						[
							'href' => $this->getPageTitle()->getLinkURL() . '/' . $key,
							'title' => $key
						],
						$key
					)
				);
			}
			$out->addHTML( Html::rawElement( 'ul', [ 'class' => 'providers-list' ], implode( '', $availableProviders ) ) );
			// TODO how to avoid showing an empty form with submit button
		}
		parent::execute( $par );
	}

	/**
	 * @inheritDoc
	 */
	protected function getDisplayFormat() {
		return 'ooui';
	}

	/**
	 * @inheritDoc
	 */
	protected function getShowAlways(): bool {
		return true;
	}

	protected function getFormFields() {
		if ( !$this->provider ) {
			return [];
		}

		$descriptors = [];

		$schema = $this->provider->getValidator()->getSchemaLoader()->getSchema();
		$properties = $schema->{'properties'};
		foreach ( $properties as $key => $value ) {
			$descriptors[$key] = $this->getFieldDescriptor( $key, (array)$value );
			$descriptors[$key]['default'] = $this->getFieldValue( $key ) ?? $value->{'default'};
		}
		return $descriptors;
	}

	/**
	 * @param string $propertyName
	 * @param array $propertySpec
	 * @return array
	 */
	protected function getFieldDescriptor( string $propertyName, array $propertySpec ): array {
		[ $type, $defaultOptions ] = $this->resolveFieldType( $propertySpec['type'], $propertySpec );
		$descriptor = [
			'name' => $propertyName,
			'label-message' => 'Label message for ' . $propertyName,
			'type' => $type,
			...$defaultOptions
		];

		return $descriptor;
	}

	/**
	 * Provide current value for a GrowthExperimentsMultiConfig variable
	 *
	 * @param string $name
	 * @return string|null
	 */
	private function getFieldValue( string $name ): ?string {
		$default = $this->provider->get( $name );

		if ( is_array( $default ) ) {
			$default = implode( "\n", $default );
		}
		if ( is_bool( $default ) ) {
			$default = $default ? 'true' : 'false';
		}

		return $default;
	}

	private function resolveFieldType( string $typeName, array $spec ) {
		$type = null;
		$options = [];
		switch ( $typeName ) {
			case 'boolean':
				$type = 'radio';
				// FIXME both labels and values for options-messages
				// should be taken from spec. Pending specification in T343072
				$options['options-messages'] = [
					'Yes' => 'true',
					'No' => 'false',
				];
				break;
			case 'string':
				$type = 'text';
				break;
			case 'array':
				// FIXME namespacesmultiselect is used for testing, the corresponding
				// field type should be taken from the spec or inspect the array type.
				// Pending decision in T343072.
				$type = 'namespacesmultiselect';
				$options['exists'] = true;
				$options['autocomplete'] = false;
				break;
		}
		return [ $type, $options ];
	}

	private function normalizeData( array $data ): array {
		$normalized = [];
		foreach ( $this->getFormFields() as $nameRaw => $descriptor ) {
			if ( $descriptor['type'] === 'namespacesmultiselect' ) {
				if ( $data[$nameRaw] === '' ) {
					$normalized[$nameRaw] = [];
				} else {
					$normalized[$nameRaw] = array_map(
						'intval',
						explode( "\n", $data[$nameRaw] )
					);
				}
			} elseif ( $descriptor['type'] === 'int' ) {
				$normalized[$nameRaw] = (int)$data[$nameRaw];
			} elseif ( $descriptor['type'] === 'text' ) {
				$normalized[$nameRaw] = $data[$nameRaw];
			}

			if ( $data[$nameRaw] === 'true' ) {
				$normalized[$nameRaw] = true;
			} elseif ( $data[$nameRaw] === 'false' ) {
				$normalized[$nameRaw] = false;
			}
		}
		return $normalized;
	}

	/**
	 * @inheritDoc
	 */
	public function onSubmit(array $data) {
		$this->checkReadOnly();

		// DO NOT rely on userCanWrite here, in case its value is wrong for some weird reason
		if ( !$this->getAuthority()->isAllowed( self::REQUIRED_RIGHT_TO_WRITE ) ) {
			throw new PermissionsError( self::REQUIRED_RIGHT_TO_WRITE );
		}

		$status = Status::newGood();
		$result = $this->provider->getValidator()->validate( $this->normalizeData( $data ) );

		return $status->merge( $result );
	}
}
