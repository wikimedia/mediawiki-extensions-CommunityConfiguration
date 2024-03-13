// Extract a definition name from its $ref path
const extractRef = ( ref ) => /#\/\$defs\/(.*)/.exec( ref )[ 1 ];
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
	return function ( uischema, schema, rootSchema ) {
		if ( tester( uischema, schema, rootSchema ) ) {
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
	return ( _uischema, schema, rootSchema ) => {
		if ( schema.$ref ) {
			return rootSchema.$defs[ extractRef( schema.$ref ) ].type === expectedType;
		}
		if ( schema.type ) {
			return schema.type === expectedType;
		}
		return false;
	};
}

/**
 * Tester function to check whether the given schema property is of
 * the expected ref. The referenced definition MUST exists or the
 * function will error out.
 *
 * @param {string} definitionName the expected $ref of the schema
 * @return {Function} A tester function for the associated type
 */
function schemaRefIs( definitionName ) {
	return ( _uischema, schema ) => {
		if ( schema.$ref ) {
			return extractRef( schema.$ref ) === definitionName;
		}
		return false;
	};
}

/**
 * Default testers for simple types
 */
const isBooleanControl = schemaTypeIs( 'boolean' );
const isStringControl = schemaTypeIs( 'string' );
const isNumberControl = schemaTypeIs( 'number' );
const isObjectControl = schemaTypeIs( 'object' );

module.exports = exports = {
	isBooleanControl,
	isNumberControl,
	isObjectControl,
	isStringControl,
	extractRef,
	rankWith,
	schemaTypeIs,
	schemaRefIs
};
