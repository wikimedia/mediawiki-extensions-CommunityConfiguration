<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use MediaWiki\Config\Config;
use MediaWiki\Extension\CommunityConfiguration\Utils;
use MediaWiki\Registration\ExtensionRegistry;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Extension\CommunityConfiguration\Utils
 */
class UtilsTest extends MediaWikiUnitTestCase {

	public static function provideMergedAttributes() {
		return [
			'no registry' => [ [ 'foo' => 1 ], [], [ 'foo' => 1 ] ],
			'no config' => [ [ 'foo' => 1 ], [ 'foo' => 1 ], [] ],
			'config has precedence' => [ [ 'foo' => 2 ], [ 'foo' => 1 ], [ 'foo' => 2 ] ],
			'multiple keys' => [ [ 'foo' => 1, 'bar' => 1 ], [ 'foo' => 1 ], [ 'bar' => 1 ] ],
		];
	}

	/**
	 * @dataProvider provideMergedAttributes
	 * @param array $expected
	 * @param array $registryData
	 * @param array $configData
	 * @return void
	 */
	public function testMergedAttributes(
		array $expected,
		array $registryData,
		array $configData
	) {
		$extensionRegistry = $this->createMock( ExtensionRegistry::class );
		$extensionRegistry->expects( $this->once() )
			->method( 'getAttribute' )
			->with( 'foo' )
			->willReturn( $registryData );
		$config = $this->createMock( Config::class );
		$config->expects( $this->once() )
			->method( 'get' )
			->willReturn( $configData );

		$this->assertEquals(
			$expected,
			Utils::getMergedAttribute(
				$config,
				$extensionRegistry,
				'foo'
			)
		);
	}
}
