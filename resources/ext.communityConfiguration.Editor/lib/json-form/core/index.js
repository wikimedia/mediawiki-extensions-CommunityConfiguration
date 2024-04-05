const { buildUISchema } = require( './uischema.js' );
const { getEditorTextKeys } = require( './i18n.js' );
const {
	isBooleanControl,
	isNumberControl,
	isStringControl,
	isObjectControl,
	extractRef,
	rankWith,
	schemaTypeIs,
	schemaRefIs
} = require( './testers.js' );

module.exports = exports = {
	buildUISchema,
	getEditorTextKeys,
	isBooleanControl,
	isNumberControl,
	isObjectControl,
	isStringControl,
	extractRef,
	rankWith,
	schemaTypeIs,
	schemaRefIs
};
