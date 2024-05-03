const BooleanControl = require( './BooleanControl.vue' );
const StringControl = require( './StringControl.vue' );
const NumberControl = require( './NumberControl.vue' );
const {
	rankWith,
	isBooleanControl,
	isIntegerControl,
	isNumberControl,
	isStringControl
} = require( '../../config/index.js' );

const rankRenderer = ( renderer, tester ) => ( {
	renderer,
	tester: rankWith( 1, tester )
} );

const isNumberOrInteger = ( ...args ) => {
	return isIntegerControl( ...args ) || isNumberControl( ...args );
};

module.exports = exports = {
	controls: [
		rankRenderer( BooleanControl, isBooleanControl ),
		rankRenderer( StringControl, isStringControl ),
		rankRenderer( NumberControl, isNumberOrInteger )
	]
};
