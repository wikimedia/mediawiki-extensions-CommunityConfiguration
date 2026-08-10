const ObjectControl = require( './ObjectControl.vue' );
const ArrayControl = require( './ArrayControl.vue' );
const EnumControl = require( './EnumControl.vue' );
const MultiselectEnumControl = require( './MultiselectEnumControl.vue' );
const ChipStringsControl = require( './ChipStringsControl.vue' );

const {
	isArrayControl,
	isEnumControl,
	isMultiSelectEnumControl,
	isObjectControl,
	isSimpleStringArrayControl,
	rankRenderer,
} = require( 'ext.communityConfiguration.Editor.controls' );

module.exports = exports = {
	complex: [
		rankRenderer( 2, MultiselectEnumControl, isMultiSelectEnumControl ),
		// isEnumControl checks not only the type schema but also the presence of enum property
		rankRenderer( 2, EnumControl, isEnumControl ),
		rankRenderer( 2, ChipStringsControl, isSimpleStringArrayControl ),
		rankRenderer( 1, ObjectControl, isObjectControl ),
		rankRenderer( 1, ArrayControl, isArrayControl ),
	],
};
