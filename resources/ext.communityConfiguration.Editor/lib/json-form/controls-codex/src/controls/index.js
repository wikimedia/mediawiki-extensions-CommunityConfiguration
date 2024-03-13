const BooleanControl = require( './BooleanControl.vue' );
const StringControl = require( './StringControl.vue' );
const {
	rankWith,
	isBooleanControl,
	isStringControl
} = require( '../../config/index.js' );

const rankRenderer = ( renderer, tester ) => ( {
	renderer,
	tester: rankWith( 1, tester )
} );

module.exports = exports = {
	controls: [
		rankRenderer( BooleanControl, isBooleanControl ),
		rankRenderer( StringControl, isStringControl )
	]
};
