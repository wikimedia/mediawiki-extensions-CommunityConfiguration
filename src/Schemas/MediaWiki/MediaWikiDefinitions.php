<?php

namespace MediaWiki\Extension\CommunityConfiguration\Schemas\MediaWiki;

use MediaWiki\Extension\CommunityConfiguration\Schema\JsonSchema;

// phpcs:disable Generic.NamingConventions.UpperCaseConstantName.ClassConstantNotUpperCase
class MediaWikiDefinitions extends JsonSchema {
	public const PageTitle = [
		self::TYPE => self::TYPE_STRING
	];
}
