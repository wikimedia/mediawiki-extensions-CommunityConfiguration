<?php

namespace MediaWiki\Extension\CommunityConfiguration\Controls;

use MediaWiki\Config\ServiceOptions;
use MediaWiki\ResourceLoader\ResourceLoader;
use Psr\Log\LoggerInterface;

/**
 * Holds the form controls that extensions register.
 *
 * An extension registers a control with the `CommunityConfiguration/Controls` attribute in its
 * extension.json:
 *
 *     "attributes": {
 *         "CommunityConfiguration": {
 *             "Controls": {
 *                 "WikiLambda.FunctionLookup": {
 *                     "module": "ext.wikilambda.functionLookup",
 *                     "component": "FunctionLookupControl"
 *                 }
 *             }
 *         }
 *     }
 *
 * A UI schema then selects the control by name. The registry drops an entry that it cannot use,
 * so that the editor falls back to a control of the data schema instead of failing.
 */
class ControlRegistry {

	/**
	 * @internal for use in ServiceWiring only
	 */
	public const CONSTRUCTOR_OPTIONS = [
		'CommunityConfigurationControls',
	];

	/** @var string Key for the ResourceLoader module that holds the control. */
	public const MODULE = 'module';

	/** @var string Key for the name the module exports the Vue component under. */
	public const COMPONENT = 'component';

	/**
	 * A control name has an extension part and a control part, for example
	 * `WikiLambda.FunctionLookup`. Two extensions that pick the same name overwrite each other
	 * without a diagnostic, so the extension part keeps the names apart.
	 */
	private const NAME_PATTERN = '/^[A-Za-z][A-Za-z0-9]*\.[A-Za-z][A-Za-z0-9]*$/';

	/** @var array Control specifications, indexed by control name */
	private array $controlSpecs;

	public function __construct(
		ServiceOptions $options,
		private readonly ResourceLoader $resourceLoader,
		private readonly LoggerInterface $logger
	) {
		$options->assertRequiredOptions( self::CONSTRUCTOR_OPTIONS );
		$this->controlSpecs = $options->get( 'CommunityConfigurationControls' );
	}

	/**
	 * Resolve control names to the module and the component that supply them.
	 *
	 * @param string[] $names Control names that a UI schema asks for
	 * @return array[] Usable controls, indexed by control name, each with a `module` and a
	 *   `component` key. A name that the registry cannot use is absent from the result.
	 */
	public function getControls( array $names ): array {
		$controls = [];
		foreach ( array_unique( $names ) as $name ) {
			$spec = $this->getUsableSpec( $name );
			if ( $spec !== null ) {
				$controls[$name] = $spec;
			}
		}
		return $controls;
	}

	/**
	 * @param string $name
	 * @return array|null The specification, or null with a warning when it is not usable
	 */
	private function getUsableSpec( string $name ): ?array {
		$spec = $this->controlSpecs[$name] ?? null;
		if ( $spec === null ) {
			$this->logger->warning(
				'CommunityConfiguration: no extension registers the control {control}.',
				[ 'control' => $name ]
			);
			return null;
		}
		if ( !preg_match( self::NAME_PATTERN, $name ) ) {
			$this->logger->warning(
				'CommunityConfiguration: the control name {control} does not follow the format of ' .
					'Extension.ControlName.',
				[ 'control' => $name ]
			);
			return null;
		}
		$module = $spec[self::MODULE] ?? null;
		$component = $spec[self::COMPONENT] ?? null;
		if ( !is_string( $module ) || !is_string( $component ) || $module === '' || $component === '' ) {
			$this->logger->warning(
				'CommunityConfiguration: the control {control} needs a module and a component.',
				[ 'control' => $name ]
			);
			return null;
		}
		if ( !$this->resourceLoader->isModuleRegistered( $module ) ) {
			// The extension that registers the control can be present while its module is not,
			// for example when the module registers only under some conditions.
			$this->logger->warning(
				'CommunityConfiguration: the control {control} needs the module {module}, ' .
					'which is not registered.',
				[ 'control' => $name, 'module' => $module ]
			);
			return null;
		}
		return [
			self::MODULE => $module,
			self::COMPONENT => $component,
		];
	}
}
