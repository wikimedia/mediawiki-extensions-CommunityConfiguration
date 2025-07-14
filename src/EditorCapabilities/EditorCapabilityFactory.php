<?php

namespace MediaWiki\Extension\CommunityConfiguration\EditorCapabilities;

use InvalidArgumentException;
use LogicException;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Context\IContextSource;
use MediaWiki\Title\Title;
use Psr\Log\LoggerInterface;
use Wikimedia\ObjectFactory\ObjectFactory;

class EditorCapabilityFactory {
	/**
	 * @internal for use in ServiceWiring only
	 */
	public const CONSTRUCTOR_OPTIONS = [
		'CommunityConfigurationEditorCapabilities',
	];

	/** @var array ObjectFactory specs for validators, indexed by validator name */
	private array $capabilitiesSpecs;

	private ObjectFactory $objectFactory;
	private LoggerInterface $logger;

	public function __construct(
		ServiceOptions $options,
		ObjectFactory $objectFactory,
		LoggerInterface $logger
	) {
		$options->assertRequiredOptions( self::CONSTRUCTOR_OPTIONS );
		$this->capabilitiesSpecs = $options->get( 'CommunityConfigurationEditorCapabilities' );

		$this->objectFactory = $objectFactory;
		$this->logger = $logger;
	}

	/**
	 * @param string $name
	 * @param IContextSource $ctx
	 * @param Title $parentTitle
	 * @return IEditorCapability
	 */
	public function newCapability(
		string $name,
		IContextSource $ctx,
		Title $parentTitle
	): IEditorCapability {
		if ( !array_key_exists( $name, $this->capabilitiesSpecs ) ) {
			throw new InvalidArgumentException( "Capability $name is not supported" );
		}

		$result = $this->objectFactory->createObject(
			$this->capabilitiesSpecs[$name],
			[
				'assertClass' => IEditorCapability::class,
				'extraArgs' => [ $ctx, $parentTitle ],
			]
		);

		// NOTE: This is here to allow for type hints.
		if ( !$result instanceof IEditorCapability ) {
			throw new LogicException( 'ObjectFactory\'s assertion is invalid' );
		}

		$result->setLogger( $this->logger );
		return $result;
	}
}
