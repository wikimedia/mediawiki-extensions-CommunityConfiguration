const ObjectControl = require( './ObjectControl.vue' );
const ArrayControl = require( './ArrayControl.vue' );

const {
	rankWith,
	isArrayControl,
	isObjectControl
} = require( '../../config/index.js' );

const rankRenderer = ( rank, renderer, tester ) => ( {
	renderer,
	tester: rankWith( rank, tester )
} );

module.exports = exports = {
	complex: [
		rankRenderer( 1, ObjectControl, isObjectControl ),
		rankRenderer( 1, ArrayControl, isArrayControl )
	]
};
