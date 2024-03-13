const ObjectControl = require( './ObjectControl.vue' );

const {
	rankWith,
	isObjectControl
} = require( '../../config/index.js' );

const rankRenderer = ( renderer, tester ) => ( {
	renderer,
	tester: rankWith( 1, tester )
} );

module.exports = exports = {
	complex: [
		rankRenderer( ObjectControl, isObjectControl )
	]
};
