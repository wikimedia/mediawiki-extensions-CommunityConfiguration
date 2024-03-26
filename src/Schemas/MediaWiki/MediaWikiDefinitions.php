<?php

namespace MediaWiki\Extension\CommunityConfiguration\Schemas\MediaWiki;

use MediaWiki\Extension\CommunityConfiguration\Schema\JsonSchema;

// phpcs:disable Generic.NamingConventions.UpperCaseConstantName.ClassConstantNotUpperCase
class MediaWikiDefinitions extends JsonSchema {
	public const PageTitle = [
		self::TYPE => self::TYPE_STRING
	];

	public const PageTitles = [
		self::TYPE => self::TYPE_ARRAY,
		self::ITEMS => [
			self::REF => [ 'class' => self::class, 'field' => 'PageTitle' ]
		]
	];

	// REVIEW: maybe create a Namespace type and reference it like PageTitles
	public const Namespaces = [
		self::TYPE => self::TYPE_ARRAY,
		self::ITEMS => [
			self::TYPE => self::TYPE_NUMBER
		]
	];
}
