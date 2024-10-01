<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests\Integration;

use MediaWiki\Extension\CommunityConfiguration\Maintenance\UpdateEmergencyDefaults;
use MediaWiki\Tests\Maintenance\MaintenanceBaseTestCase;
use stdClass;

/**
 * @covers \MediaWiki\Extension\CommunityConfiguration\Maintenance\UpdateEmergencyDefaults
 */
class UpdateEmergencyDefaultsTest extends MaintenanceBaseTestCase {

	/**
	 * @inheritDoc
	 */
	protected function getMaintenanceClass() {
		return UpdateEmergencyDefaults::class;
	}

	public function testUpdateOK() {
		// phpcs:disable: MediaWiki.NamingConventions.ValidGlobalName.allowedPrefix
		global $IP;
		$path = implode( DIRECTORY_SEPARATOR, [
			$IP, 'extensions', 'CommunityConfigurationExample', 'CommunityConfigurationFallbacks',
			'CommunityConfigurationExample.php',
		] );
		if ( file_exists( $path ) ) {
			// Ensure the file does not exist
			unlink( $path );
		}
		$this->assertFileDoesNotExist(
			$path,
			'Failed to unlink the file with emergency defaults for CommunityConfigurationExample'
		);

		$this->maintenance->loadParamsAndArgs( null, [], [
			'CommunityConfigurationExample', 'CommunityConfigurationExample',
		] );
		$this->maintenance->execute();

		// Assert the defaults file exists and returns (some) object
		// NOTE: This doesn't assert the defaults are of the expected value, but that should be
		// covered by EmergencyDefaultsUpdaterIntegrationTest and EmergencyDefaultsUpdaterTest.
		$this->assertFileExists( $path );
		$this->assertInstanceOf(
			stdClass::class,
			require_once $path
		);
		unlink( $path );
	}
}
