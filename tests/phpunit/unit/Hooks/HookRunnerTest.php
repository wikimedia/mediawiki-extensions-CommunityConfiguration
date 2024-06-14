<?php

namespace MediaWiki\Extension\CommunityConfiguration\Tests;

use MediaWiki\Extension\CommunityConfiguration\Hooks\HookRunner;
use MediaWiki\Tests\HookContainer\HookRunnerTestBase;

/**
 * @covers \MediaWiki\Extension\CommunityConfiguration\Hooks\HookRunner
 */
class HookRunnerTest extends HookRunnerTestBase {
	public static function provideHookRunners() {
		yield HookRunner::class => [ HookRunner::class ];
	}
}
