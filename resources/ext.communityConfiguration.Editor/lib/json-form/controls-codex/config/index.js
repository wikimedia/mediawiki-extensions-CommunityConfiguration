const {
	buildUISchema,
	rankWith,
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
	rendererProps,
	useJsonFormControl,
	useJsonFormRenderer
};
