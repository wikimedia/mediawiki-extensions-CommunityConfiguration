const { buildUISchema, buildUISubSchema } = require( './uischema.js' );
const { getEditorTextKeys } = require( './i18n.js' );
const {
	isArrayControl,
	isBooleanControl,
	isIntegerControl,
	isNumberControl,
	isStringControl,
	isEnumControl,
	isSimpleStringArrayControl,
	isMultiSelectEnumControl,
	isObjectControl,
	extractRef,
	rankWith,
	schemaControlIs,
	schemaTypeIs,
} = require( './testers.js' );

module.exports = exports = {
	buildUISchema,
	buildUISubSchema,
	getEditorTextKeys,
	isArrayControl,
	isBooleanControl,
	isIntegerControl,
	isNumberControl,
	isObjectControl,
	isStringControl,
	isEnumControl,
	isSimpleStringArrayControl,
	isMultiSelectEnumControl,
	extractRef,
	rankWith,
	schemaControlIs,
	schemaTypeIs,
};
