# Introducing a UISchema to CommunityConfiguration with supporting custom controls as the first step

## Status

Accepted (2026-08-03)

## Context and Problem Statement

Both from the Abstract Wikipedia team and from the Reader Experience team, there have been recent requests to be able to use custom form controls for some use-case or other.

CommunityConfiguration is closely inspired in its architecture by JSON Froms (https://jsonforms.io/). The approach that JSON Forms uses for such customization is a UISchema (https://jsonforms.io/docs/uischema/). CommunityConfiguration is using/creating a very simple UISchema internally in the frontend part of the code, which essentially only is "Render a control for all fields in the data schema, one after the other."

The proper way to allow consumers of CommunityConfiguration to gain more control over how the form is rendered is to allow them to provide their own UISchema.

## Decision Outcome
To unblock the teams asking for custom controls, the first iteration of UISchema in CommunityConfiguration should be built to allow extensions exactly that.

The proposed implementation should have the following properties:
* **backwards compatibility**: existing schemas without an associated UISchema continue behaving in exactly the same way
* **graceful degradation**: if a control defined by a UISchema is not available, CommunityConfiguration uses its internal logic to pick a suitable control
* **separation of concerns**: the new UISchema should be exclusively for _presentational_ aspects, everything around _validation_ must continue to live in the data-schema

Specifically, building on the example of [T424097](https://phabricator.wikimedia.org/T424097):

We want to get to a state where existing data-schema would have an optional reference to the associated UISchema, like so:

```
class SuggestedFunctionsSchema extends JsonSchema {

	public const UI_SCHEMA = SuggestedFunctionsUISchema::class;

	public const SuggestedFunctionsEnabled = [
		self::TYPE => self::TYPE_BOOLEAN,
		self::DEFAULT => false,
	];

	public const SuggestedFunctions = [
		self::TYPE => self::TYPE_ARRAY,
		self::ITEMS => [
			self::TYPE => self::TYPE_STRING,
			self::PATTERN => '^Z[1-9]\\d*$',
		],
		self::MAX_ITEMS => 5,
		self::DEFAULT => [ 'Z20756', 'Z18428' ],
	];

	public const Example_String = [
		self::TYPE => self::TYPE_STRING,
		self::DEFAULT => '',
		self::MAX_LENGTH => 50,
	];
}
```

With the referenced UISchema looking something like this:

```
class SuggestedFunctionsUISchema extends UISchema {

      public const ROOT = [
          self::ELEMENTS => [
              '#/properties/SuggestedFunctionsEnabled',
              [
                  self::TYPE => self::TYPE_CONTROL,
                  self::SCOPE => '#/properties/SuggestedFunctions',
                  self::CONTROL => 'WikiLambda.FunctionSelector',
                  self::OPTIONS => [
                      // presentation-only knobs the control understands,
                      // e.g. 'showZidChips' => true
                  ],
                  self:MESSAGES => [
                      // the (extra?) message keys needed for this control
                  ],
              ],
              '#/properties/Example_String',
          ],
      ];
  }
```

Note that `self::CONTROL` should usually be a plain string and not a PHP FQDN. A PHP FQDN seems to mix abstractions and has details like namespaces leak.

The control itself would be registered as an attribute, similar to what is sketched out in T424097.

CommunityConfiguration needs to be extended so that likely `GenericFormEditorCapability` understands and scans the associated UISchemas and adds the requested js modules (and i18n messages, probably those should also live in the extension.json section).

The client-side registry needs to load the custom controls.
This is probably the least well-defined part. Currently, this is a closed system that just loads all the handlers CommmunityConfiguration has defined and that needs to change. TBD on how exactly that would look, whether via `mw.communityConfiguration.registerControl( name, renderer, tester )` or `mw.hook( 'CommunityConfiguration_loading_controls')` or some other approach. All of that then needs to be wired up.

The UISchema should typically contain _all_ elements of a data schema because the elements will be rendered in the order they appear in the Schema.
This keeps the UISchema forward compatible for when in later iterations grouping, adding headlines, etc., are introduced.
Missing elements should still be appended to the form according to the existing default layout logic so that no elements are lost.

## Open Questions
* How specifically would the js code be loaded? This is the issue described above. We need to decide this once, it will be hard to change later.

## Notes
* Especially the first extensions making use of the UISchema will need _excellent_ testing and a mutual CI-dependency with CommunityConfiguration to make sure that future work on CommunityConfigration+UISchema does not silently change how their forms look.
