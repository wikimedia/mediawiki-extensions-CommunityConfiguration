const BooleanControl = require( './BooleanControl.vue' );
const StringControl = require( './StringControl.vue' );
const NumberControl = require( './NumberControl.vue' );
const {
	isBooleanControl,
	isIntegerControl,
	isNumberControl,
	isStringControl,
	rankRenderer,
} = require( 'ext.communityConfiguration.Editor.controls' );

const isNumberOrInteger = ( ...args ) => isIntegerControl( ...args ) || isNumberControl( ...args );

module.exports = exports = {
	controls: [
		rankRenderer( 1, BooleanControl, isBooleanControl ),
		rankRenderer( 1, StringControl, isStringControl ),
		rankRenderer( 1, NumberControl, isNumberOrInteger ),
	],
};
