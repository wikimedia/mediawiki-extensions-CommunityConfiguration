/**
 * Create a ranked tester that will associate a number with a given
 * tester, if the latter returns true.
 *
 * @param {number} rank the rank to be returned when
 * the tester evaluates to true
 * @param {Function} tester a tester function
 * @return {Function} The ranked tester function
 */
function rankWith( rank, tester ) {
	return function ( uischema, schema ) {
		if ( tester( uischema, schema ) ) {
			return rank;
		}

		return false;
	};
}

/**
 * Tester function to check whether the given schema property is of
 * the expected type.
 *
 * @param {string} expectedType the expected type of the schema
 * @return {Function} A tester function for the associated type
 */
function schemaTypeIs( expectedType ) {
	return ( _uischema, schema ) => schema.type === expectedType;
}

/**
 * Default testers for simple types
 */
const isBooleanControl = schemaTypeIs( 'boolean' );
const isStringControl = schemaTypeIs( 'string' );
const isNumberControl = schemaTypeIs( 'number' );

module.exports = exports = {
	isBooleanControl,
	isNumberControl,
	isStringControl,
	rankWith
};
