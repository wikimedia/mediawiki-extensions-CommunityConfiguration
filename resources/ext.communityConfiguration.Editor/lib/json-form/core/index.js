const { buildUISchema } = require( './uischema.js' );
const { getEditorTextKeys } = require( './i18n.js' );
const {
	rankWith,
	isBooleanControl,
	isNumberControl,
	isStringControl,
	isObjectControl
} = require( './testers.js' );

module.exports = exports = {
	buildUISchema,
	getEditorTextKeys,
	rankWith,
	isBooleanControl,
	isNumberControl,
	isObjectControl,
	isStringControl
};
