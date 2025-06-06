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
	return ( _uischema, schema ) => {
		if ( schema.type ) {
			return schema.type === expectedType;
		}
		return false;
	};
}

function isEnumControl( _uischema, schema ) {
	const validEnumTypes = [ 'string', 'number' ];
	// TODO: replace this indexOf with includes, once eslint config is updated
	return validEnumTypes.includes( schema.type ) && Array.isArray( schema.enum );
}

function isSimpleStringArrayControl( _uischema, schema ) {
	return schema.type === 'array' && schema.items && schema.items.type === 'string';
}

function isMultiSelectEnumControl( _uischema, schema ) {
	const validEnumTypes = [ 'string', 'number' ];
	return schema.type === 'array' &&
		schema.items &&
		schema.items.enum &&
		Array.isArray( schema.items.enum ) &&
		validEnumTypes.includes( schema.items.type );
}

/**
 * Tester function to check whether the given schema has
 * the expected control.
 *
 * @param {string} controlName the expected value of the control
 * property in the schema. In practice, a PHP fully qualified class name.
 * @return {Function} A tester function for the associated type
 */
function schemaControlIs( controlName ) {
	return ( _uischema, schema ) => {
		if ( schema.control ) {
			return schema.control === controlName;
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
const isIntegerControl = schemaTypeIs( 'integer' );
const isObjectControl = schemaTypeIs( 'object' );
const isArrayControl = schemaTypeIs( 'array' );

module.exports = exports = {
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
