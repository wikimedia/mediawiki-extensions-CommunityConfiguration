<?php

namespace MediaWiki\Extension\CommunityConfiguration\Store\WikiPage;

use FormatJson;
use MediaWiki\Extension\CommunityConfiguration\CommunityConfigurationServices;
use MediaWiki\Title\Title;
use MediaWikiIntegrationTestCase;
use StatusValue;
use stdClass;

/**
 * @covers \MediaWiki\Extension\CommunityConfiguration\Store\WikiPage\Loader
 * @group Database
 */
class LoaderIntegrationTest extends MediaWikiIntegrationTestCase {

	private const CONFIG_PAGE_TITLE = 'MediaWiki:Foo.json';

	protected function setUp(): void {
		parent::setUp();
		$this->setMwGlobals( [
			'wgCommunityConfigurationProviders' => [
				'foo' => [
					'store' => [
						'type' => 'wikipage',
						'args' => [ self::CONFIG_PAGE_TITLE ],
					],
					'validator' => [
						'type' => 'noop',
					],
				]
			]
		] );
	}

	public static function provideDataIsMutable() {
		return [
			'simple data' => [
				(object)[
					'a' => 1,
					'b' => 2,
				],
				static function ( StatusValue $statusValue ) {
					$data = $statusValue->getValue();
					unset( $data->b );
				}
			],
			'recursive data' => [
				(object)[
					'a' => 1,
					'b' => (object)[ 'a' => 1, 'b' => 2 ],
				],
				static function ( StatusValue $statusValue ) {
					$data = $statusValue->getValue();
					unset( $data->b->a );
				}
			],
			'StatusValue' => [
				(object)[
					'a' => 1,
					'b' => 2,
				],
				static function ( StatusValue $statusValue ) {
					$statusValue->setResult( false );
				}
			]
		];
	}

	/**
	 * @param stdClass $originalConfig
	 * @param callable $manipulateStatus
	 * @dataProvider provideDataIsMutable
	 */
	public function testDataIsMutableOK( stdClass $originalConfig, callable $manipulateStatus ) {
		$this->editPage( self::CONFIG_PAGE_TITLE, FormatJson::encode( $originalConfig ) );

		$loader = CommunityConfigurationServices::wrap( $this->getServiceContainer() )
			->getWikiPageStoreLoader();

		// assert loading works correctly
		$loaderStatus = $loader->load( Title::newFromText( self::CONFIG_PAGE_TITLE ) );
		$this->assertStatusOK( $loaderStatus );
		$this->assertStatusValue(
			$originalConfig,
			$loaderStatus,
			'Loader does not return data correctly'
		);

		// manipulating $loaderStatus should not have side effects
		$manipulateStatus( $loaderStatus );

		// assert loading again produces the same result
		$loaderStatus = $loader->load( Title::newFromText( self::CONFIG_PAGE_TITLE ) );
		$this->assertStatusOK( $loaderStatus );
		$this->assertStatusValue(
			$originalConfig,
			$loaderStatus,
			'Loader class allows callers to corrupt its cache on success'
		);
	}

	public function testStatusIsMutableFail() {
		$this->editPage( 'NotJsonContent', 'this is not JSON' );

		$loader = CommunityConfigurationServices::wrap( $this->getServiceContainer() )
			->getWikiPageStoreLoader();

		// assert loading fails
		$status = $loader->load( Title::newFromText( 'NotJsonContent' ) );
		$this->assertStatusNotOK( $status );
		$this->assertStatusValue( null, $status );

		// manipulating the $status should not have any side effects
		$status->setResult( true, (object)[ 'a' => 42 ] );

		// assert loading produces the same result
		$status = $loader->load( Title::newFromText( 'NotJsonContent' ) );
		$this->assertStatusNotOK( $status );
		$this->assertStatusValue( null, $status );
	}
}
