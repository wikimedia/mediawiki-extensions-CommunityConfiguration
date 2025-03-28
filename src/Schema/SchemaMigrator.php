<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\CommunityConfiguration\Schema;

use LogicException;
use MediaWiki\Extension\CommunityConfiguration\Provider\IConfigurationProvider;
use MediaWiki\Extension\CommunityConfiguration\Validation\IValidator;
use StatusValue;
use stdClass;

/**
 * Generic class to convert data to a specified target version (by repetatively constructing an
 * ISchemaConverter).
 */
class SchemaMigrator {

	private SchemaConverterFactory $schemaConverterFactory;

	public function __construct( SchemaConverterFactory $schemaConverterFactory ) {
		$this->schemaConverterFactory = $schemaConverterFactory;
	}

	/**
	 * Convert data from a provider to a particular target version
	 *
	 * @param IConfigurationProvider $provider
	 * @param string $targetVersion
	 * @throws LogicException when not convertable due to missing version data/support
	 * @return StatusValue
	 */
	public function convertDataToVersion(
		IConfigurationProvider $provider,
		string $targetVersion
	): StatusValue {
		$status = $provider->loadValidConfiguration();
		if ( !$status->isOK() ) {
			return $status;
		}

		$currentVersion = $provider->getStore()->getVersion();
		if ( $currentVersion === null ) {
			throw new LogicException( __METHOD__ . ' lacks version data' );
		}

		return StatusValue::newGood( $this->doConvertDataToVersion(
			$provider->getValidator(),
			$status->getValue(),
			$currentVersion,
			$targetVersion
		) );
	}

	/**
	 * Recursively convert data to the target version
	 *
	 * @param IValidator $validator
	 * @param stdClass $data
	 * @param string $currentVersion
	 * @param string $targetVersion
	 * @return stdClass
	 */
	private function doConvertDataToVersion(
		IValidator $validator,
		stdClass $data,
		string $currentVersion,
		string $targetVersion
	): stdClass {
		$versionComparsion = version_compare( $currentVersion, $targetVersion );
		if ( $versionComparsion === 0 ) {
			// Nothing to convert
			return $data;
		}

		$schemaBuilder = $validator->getSchemaBuilder();
		$currentSchemaReader = $schemaBuilder->getVersionManager()->getVersionForSchema( $currentVersion );
		if ( $versionComparsion < 0 ) {
			// current version is lower than $targetVersion, we need to upgrade
			$nextVersion = $currentSchemaReader->getNextVersion();
		} else {
			// current version is lower than $targetVersion, we need to downgrade
			$nextVersion = $currentSchemaReader->getPreviousVersion();
		}

		if ( $nextVersion === null ) {
			throw new LogicException(
				$currentSchemaReader->getSchemaId()
				. ' does not have a next/previous version linked.'
			);
		}

		if ( $versionComparsion < 0 ) {
			// current version is lower than $targetVersion, we need to upgrade
			$newerVersionConverter = $this->schemaConverterFactory->getConverterFromVersion(
				$schemaBuilder,
				$nextVersion
			);
			if ( !$newerVersionConverter ) {
				throw new LogicException(
					$validator->getSchemaBuilder()->getSchemaName()
					. $nextVersion . ' does not have a converter.'
				);
			}
			$newData = $newerVersionConverter->upgradeFromOlder( $data );
		} else {
			// current version is newer than $targetVersion, we need to downgrade
			$currentVersionConverter = $this->schemaConverterFactory->getConverterFromVersion(
				$schemaBuilder,
				$currentVersion
			);
			if ( !$currentVersionConverter ) {
				throw new LogicException(
					$validator->getSchemaBuilder()->getSchemaName()
					. $currentVersion . ' does not have a converter.'
				);
			}
			$newData = $currentVersionConverter->downgradeToPrevious( $data );
		}

		return $this->doConvertDataToVersion(
			$validator,
			$newData,
			$nextVersion,
			$targetVersion
		);
	}
}
