const {
	buildUISchema,
	rankWith,
	schemaRefIs,
	isBooleanControl,
	isNumberControl,
	isStringControl,
	isObjectControl
} = require( '../../core/index.js' );
const {
	rendererProps,
	useJsonFormControl,
	useJsonFormRenderer,
	DispatchRenderer
} = require( '../../form/index.js' );

module.exports = exports = {
	buildUISchema,
	DispatchRenderer,
	isBooleanControl,
	isStringControl,
	isNumberControl,
	isObjectControl,
	rankWith,
	schemaRefIs,
	rendererProps,
	useJsonFormControl,
	useJsonFormRenderer
};
