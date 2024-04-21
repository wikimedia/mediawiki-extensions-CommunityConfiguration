const { buildUISchema, buildUISubSchema } = require( './uischema.js' );
const { getEditorTextKeys } = require( './i18n.js' );
const {
	isArrayControl,
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
	buildUISubSchema,
	getEditorTextKeys,
	isArrayControl,
	isBooleanControl,
	isNumberControl,
	isObjectControl,
	isStringControl,
	extractRef,
	rankWith,
	schemaTypeIs,
	schemaRefIs
};
