const BooleanControl = require( './BooleanControl.vue' );
const {
	rankWith,
	isBooleanControl
} = require( '../../config/index.js' );

const rankRenderer = ( renderer, tester ) => ( {
	renderer,
	tester: rankWith( 1, tester )
} );

module.exports = exports = {
	controls: [
		rankRenderer( BooleanControl, isBooleanControl )
	]
};
