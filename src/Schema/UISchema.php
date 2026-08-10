<?php

namespace MediaWiki\Extension\CommunityConfiguration\Schema;

/**
 * Base class for a UI schema.
 *
 * A JsonSchema tells you which data the configuration holds. A UI schema tells you how the
 * editor form shows that data. The UI schema has no effect on the stored value, and no effect
 * on validation.
 *
 * A data schema points to its UI schema with the UI_SCHEMA constant:
 *
 *     public const UI_SCHEMA = MyUISchema::class;
 *
 * The layout is in the ROOT constant of that class. ROOT holds a list of elements. Each element
 * is one of these:
 *
 *   - A scope string, for example `#/properties/Foo`. This shows one control with the default
 *     settings.
 *   - A Control array. Use this to select a different control, or to give the control options.
 *   - A Group array. This shows a labelled section that holds more elements.
 *
 * The elements show in the order of the list. If the UI schema does not include a property of the
 * data schema, the form adds that property at the end. No property can disappear from the form.
 *
 * Example:
 *
 *     class MyUISchema extends UISchema {
 *         public const ROOT = [
 *             self::ELEMENTS => [
 *                 '#/properties/Enabled',
 *                 [
 *                     self::TYPE => self::TYPE_CONTROL,
 *                     self::SCOPE => '#/properties/Functions',
 *                     self::CONTROL => 'WikiLambda.FunctionLookup',
 *                     self::OPTIONS => [ 'outputType' => 'Z89' ],
 *                 ],
 *             ],
 *         ];
 *     }
 *
 * @see JsonSchema::UI_SCHEMA
 * @stable to extend
 */
abstract class UISchema {

	/**
	 * @var array Root layout definition, shaped as `[ self::ELEMENTS => [ ... ] ]`
	 * @stable to override
	 *
	 * An empty ROOT has the same effect as no UI schema at all.
	 */
	public const ROOT = [];

	/*
	 * Element types
	 */

	/** @var string A single form control for one property. */
	public const TYPE_CONTROL = 'Control';

	/** @var string A labelled section that holds more elements. */
	public const TYPE_GROUP = 'Group';

	/*
	 * Element keys
	 */

	/** @var string The type of the element. Use one of the TYPE_* constants. */
	public const TYPE = 'type';

	/** @var string A JSON pointer to the property, for example `#/properties/Foo`. */
	public const SCOPE = 'scope';

	/** @var string The list of child elements of a Group. */
	public const ELEMENTS = 'elements';

	/**
	 * @var string The i18n label stem of a Group.
	 *
	 * The editor makes the message keys `<prefix>-<providerId>-<label>-section-label` and
	 * `-section-description` from this value.
	 */
	public const LABEL = 'label';

	/**
	 * @var string The name of the control to use for a Control element.
	 *
	 * The name is a plain string, not a PHP class name. CommunityConfiguration has some
	 * control names of its own. An extension registers more names with the
	 * `CommunityConfiguration/Controls` attribute in its extension.json.
	 *
	 * If the named control is not available, the editor selects a control from the data
	 * schema instead.
	 */
	public const CONTROL = 'control';

	/**
	 * @var string Presentation options for the control, as a map.
	 *
	 * Each control decides which options it understands. Do not put anything here that
	 * changes which values are valid. Validation stays in the data schema.
	 */
	public const OPTIONS = 'options';

	/**
	 * @var string Extra message keys that the control needs, as a list.
	 *
	 * The editor sends these messages to the client together with the messages of the
	 * data schema.
	 */
	public const MESSAGES = 'messages';
}
