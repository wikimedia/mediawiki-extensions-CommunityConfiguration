const { buildUISchema } = require( './uischema.js' );
const { getControlsTextKeys } = require( './i18n.js' );
const {
	rankWith,
	isBooleanControl,
	isNumberControl,
	isStringControl
} = require( './testers.js' );

module.exports = exports = {
	buildUISchema,
	getControlsTextKeys,
	rankWith,
	isBooleanControl,
	isNumberControl,
	isStringControl
};
