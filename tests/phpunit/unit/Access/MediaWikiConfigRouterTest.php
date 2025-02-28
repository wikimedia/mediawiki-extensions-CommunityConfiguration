<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use MediaWiki\Config\Config;
use MediaWiki\Config\ConfigException;
use MediaWiki\Extension\CommunityConfiguration\Access\MediaWikiConfigReader;
use MediaWiki\Extension\CommunityConfiguration\Access\MediaWikiConfigRouter;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Extension\CommunityConfiguration\Access\MediaWikiConfigRouter
 */
class MediaWikiConfigRouterTest extends MediaWikiUnitTestCase {

	public static function provideHas() {
		return [
			'only main config' => [ true, false, true ],
			'only reader' => [ true, true, false ],
			'both' => [ true, true, true ],
			'none' => [ false, false, false ],
		];
	}

	/**
	 * @dataProvider provideHas
	 */
	public function testHas( bool $expected, bool $routerHas, bool $mainHas ) {
		$reader = $this->createNoOpMock( MediaWikiConfigReader::class, [ 'has' ] );
		$reader->expects( $this->atMost( 1 ) )
			->method( 'has' )
			->with( 'foo' )
			->willReturn( $routerHas );
		$mainConfig = $this->createNoOpMock( Config::class, [ 'has' ] );
		$mainConfig->expects( $this->atMost( 1 ) )
			->method( 'has' )
			->with( 'foo' )
			->willReturn( $mainHas );

		$router = new MediaWikiConfigRouter( $reader, $mainConfig );
		$this->assertSame( $expected, $router->has( 'foo' ) );
	}

	public function testGetFromReader() {
		$reader = $this->createMock( MediaWikiConfigReader::class );
		$reader->expects( $this->once() )
			->method( 'has' )
			->with( 'foo' )
			->willReturn( true );
		$reader->expects( $this->once() )
			->method( 'get' )
			->with( 'foo' )
			->willReturn( 'bar' );

		$router = new MediaWikiConfigRouter( $reader, $this->createNoOpMock( Config::class ) );
		$this->assertSame( 'bar', $router->get( 'foo' ) );
	}

	public function testGetFromMainConfig() {
		$reader = $this->createMock( MediaWikiConfigReader::class );
		$reader->expects( $this->once() )
			->method( 'has' )
			->with( 'foo' )
			->willReturn( false );
		$reader->expects( $this->never() )
			->method( 'get' );

		$config = $this->createMock( Config::class );
		$config->expects( $this->atLeast( 1 ) )
			->method( 'has' )
			->with( 'foo' )
			->willReturn( true );
		$config->expects( $this->once() )
			->method( 'get' )
			->with( 'foo' )
			->willReturn( 'baz' );

		$router = new MediaWikiConfigRouter( $reader, $config );
		$this->assertSame( 'baz', $router->get( 'foo' ) );
	}

	public function testGetUnsupported() {
		$reader = $this->createMock( MediaWikiConfigReader::class );
		$reader->expects( $this->atMost( 1 ) )
			->method( 'has' )
			->with( 'unsupported' )
			->willReturn( false );
		$reader->expects( $this->never() )
			->method( 'get' );

		$config = $this->createMock( Config::class );
		$config->expects( $this->atMost( 1 ) )
			->method( 'has' )
			->with( 'unsupported' )
			->willReturn( false );
		$config->expects( $this->never() )
			->method( 'get' );

		$router = new MediaWikiConfigRouter( $reader, $config );
		$this->expectException( ConfigException::class );
		$router->get( 'unsupported' );
	}
}
