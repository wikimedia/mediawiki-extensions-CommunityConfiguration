const ObjectControl = require( './ObjectControl.vue' );
const ArrayControl = require( './ArrayControl.vue' );
const EnumControl = require( './EnumControl.vue' );
const ChipStringsControl = require( './ChipStringsControl.vue' );

const {
	rankWith,
	isArrayControl,
	isObjectControl,
	isEnumControl,
	isSimpleStringArrayControl
} = require( '../../config/index.js' );

const rankRenderer = ( rank, renderer, tester ) => ( {
	renderer,
	tester: rankWith( rank, tester )
} );

module.exports = exports = {
	complex: [
		// isEnumControl checks not only the type schema but also the presence of enum property
		rankRenderer( 2, EnumControl, isEnumControl ),
		rankRenderer( 2, ChipStringsControl, isSimpleStringArrayControl ),
		rankRenderer( 1, ObjectControl, isObjectControl ),
		rankRenderer( 1, ArrayControl, isArrayControl )
	]
};
