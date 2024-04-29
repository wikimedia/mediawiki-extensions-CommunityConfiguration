const {
	buildUISchema,
	buildUISubSchema,
	rankWith,
	schemaControlIs,
	isArrayControl,
	isBooleanControl,
	isNumberControl,
	isStringControl,
	isObjectControl
} = require( '../../core/index.js' );
const {
	rendererProps,
	useJsonFormArrayControl,
	useJsonFormControl,
	useJsonFormRenderer,
	DispatchRenderer
} = require( '../../form/index.js' );

module.exports = exports = {
	buildUISchema,
	buildUISubSchema,
	DispatchRenderer,
	isArrayControl,
	isBooleanControl,
	isStringControl,
	isNumberControl,
	isObjectControl,
	rankWith,
	schemaControlIs,
	rendererProps,
	useJsonFormArrayControl,
	useJsonFormControl,
	useJsonFormRenderer
};
