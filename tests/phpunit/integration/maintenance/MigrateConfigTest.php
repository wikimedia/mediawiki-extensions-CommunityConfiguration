<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\CommunityConfiguration\Tests\Integration;

use MediaWiki\Extension\CommunityConfiguration\CommunityConfigurationServices;
use MediaWiki\Extension\CommunityConfiguration\Maintenance\MigrateConfig;
use MediaWiki\Page\WikiPage;
use MediaWiki\Tests\Maintenance\MaintenanceBaseTestCase;
use MediaWiki\Title\Title;

/**
 * @group Database
 * @covers \MediaWiki\Extension\CommunityConfiguration\Maintenance\MigrateConfig
 */
class MigrateConfigTest extends MaintenanceBaseTestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->markTestSkippedIfExtensionNotLoaded( 'CommunityConfigurationExample' );
	}

	protected function getMaintenanceClass(): string {
		return MigrateConfig::class;
	}

	public function testUpgrade(): void {
		$pageStatus = $this->editPage( 'MediaWiki:CommunityConfigurationExample.json', '{
	"$version": "1.0.0",
	"CCExample_OnOff": "on"
}' );
		$this->assertStatusGood( $pageStatus );

		// Pin the target version: asserting the complete configuration only works against a
		// known schema version, and the most recent one moves whenever CCExample gains a
		// schema. testUpgradeDefaultsToMostRecentVersion covers the implicit target instead.
		$this->maintenance->loadParamsAndArgs(
			null,
			[ 'version' => '1.1.0' ],
			[ 'CommunityConfigurationExample' ]
		);
		$result = $this->maintenance->execute();
		$this->assertTrue( $result );

		$expectedConfig = (object)[
			'$version' => '1.1.0',
			"CCExample_OnOff" => true,
			'CCExample_String' => '',
			'CCExample_FavoriteColors' => [],
			'CCExample_Numbers' => (object)[
				'IntegerNumber' => 0,
				'DecimalNumber' => 0.6,
			],
			"CCExample_RelevantPages" => [],
			'CCExample_CustomControl' => 0,
			'CCExample_ValueA' => 0,
			'CCExample_ValueB' => '',
		];

		/** @var WikiPage $wikipage */
		$wikipage = $this->getServiceContainer()->get( 'WikiPageFactory' )->newFromTitle(
			Title::newFromText( 'MediaWiki:CommunityConfigurationExample.json' )
		);
		$actualDataStatus = $wikipage->getContent()->getData();
		$this->assertStatusGood( $actualDataStatus );
		$this->assertEquals( $expectedConfig, $actualDataStatus->getValue() );
	}

	public function testUpgradeDefaultsToMostRecentVersion(): void {
		$pageStatus = $this->editPage( 'MediaWiki:CommunityConfigurationExample.json', '{
	"$version": "1.0.0",
	"CCExample_OnOff": "on"
}' );
		$this->assertStatusGood( $pageStatus );

		$provider = CommunityConfigurationServices::wrap( $this->getServiceContainer() )
			->getConfigurationProviderFactory()
			->newProvider( 'CommunityConfigurationExample' );
		$mostRecentVersion = $provider->getValidator()->getSchemaVersion();
		$this->assertNotSame( '1.0.0', $mostRecentVersion,
			'CCExample must define more than one schema version for this test to mean anything' );

		// No --version: MigrateConfig has to fall back to the most recent schema version.
		$this->maintenance->loadParamsAndArgs(
			null,
			[],
			[ 'CommunityConfigurationExample' ]
		);
		$result = $this->maintenance->execute();
		$this->assertTrue( $result );

		// Only the resulting version is asserted; the configuration shape belongs to whichever
		// schema version happens to be the most recent one, so it is not this test's business.
		$loadStatus = $provider->getStore()->loadVersionedConfigurationUncached();
		$this->assertStatusGood( $loadStatus );
		$this->assertSame( $mostRecentVersion, $loadStatus->getValue()->getVersion() );
	}

	public function testDowngrade(): void {
		$pageStatus = $this->editPage( 'MediaWiki:CommunityConfigurationExample.json', '{
	"$version": "1.1.0",
	"CCExample_OnOff": true
}' );
		$this->assertStatusGood( $pageStatus );

		$this->maintenance->loadParamsAndArgs(
			null,
			[ 'version' => '1.0.0' ],
			[ 'CommunityConfigurationExample' ]
		);

		$result = $this->maintenance->execute();
		$this->assertTrue( $result );

		$expectedConfig = (object)[
			'$version' => '1.0.0',
			"CCExample_OnOff" => "on",
			'CCExample_String' => '',
			'CCExample_FavoriteColors' => [],
			'CCExample_Numbers' => (object)[
				'IntegerNumber' => 0,
				'DecimalNumber' => 0.6,
			],
			"CCExample_RelevantPages" => [],
		];

		/** @var WikiPage $wikipage */
		$wikipage = $this->getServiceContainer()->get( 'WikiPageFactory' )->newFromTitle(
			Title::newFromText( 'MediaWiki:CommunityConfigurationExample.json' )
		);
		$actualDataStatus = $wikipage->getContent()->getData();
		$this->assertStatusGood( $actualDataStatus );
		$this->assertEquals( $expectedConfig, $actualDataStatus->getValue() );
	}
}
