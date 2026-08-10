const BooleanControl = require( './BooleanControl.vue' );
const StringControl = require( './StringControl.vue' );
const NumberControl = require( './NumberControl.vue' );
const {
	rankRenderer,
	isBooleanControl,
	isIntegerControl,
	isNumberControl,
	isStringControl,
} = require( '../../config/index.js' );

const isNumberOrInteger = ( ...args ) => isIntegerControl( ...args ) || isNumberControl( ...args );

module.exports = exports = {
	controls: [
		rankRenderer( 1, BooleanControl, isBooleanControl ),
		rankRenderer( 1, StringControl, isStringControl ),
		rankRenderer( 1, NumberControl, isNumberOrInteger ),
	],
};
