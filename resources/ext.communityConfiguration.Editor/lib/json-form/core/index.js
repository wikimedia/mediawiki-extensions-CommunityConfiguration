const { buildUISchema } = require( './uischema.js' );
const {
	rankWith,
	isBooleanControl,
	isNumberControl,
	isStringControl
} = require( './testers.js' );

module.exports = exports = {
	buildUISchema,
	rankWith,
	isBooleanControl,
	isNumberControl,
	isStringControl
};
