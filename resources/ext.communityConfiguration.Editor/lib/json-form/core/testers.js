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
 * Pair a renderer with a ranked tester, for a renderers collection.
 *
 * DispatchRenderer selects the renderer with the highest rank. The ranks in use are:
 *
 *   1  controls for a simple type, for example a string or a boolean
 *   2  controls for a complex type, for example an enumeration or an array
 *   3  controls that the data schema selects by name, for example PageTitleControl
 *   4-9  layouts of the UI schema. A layout must be above 3, because the tester of a
 *        control looks at the data schema, which for a layout element is the schema of
 *        the parent, and isObjectControl would match it.
 *   10 controls that the UI schema selects by name, including the controls of other
 *      extensions. These are above every built-in control, because the UI schema states
 *      an intention while the built-in testers only make a guess from the data type.
 *
 * @param {number} rank the rank the tester returns when it matches. It must be
 * 1 or more, because DispatchRenderer ignores a rank of 0.
 * @param {Object} renderer a Vue component
 * @param {Function} tester a tester function
 * @return {{renderer: Object, tester: Function}} The renderers collection entry
 */
function rankRenderer( rank, renderer, tester ) {
	return {
		renderer,
		tester: rankWith( rank, tester ),
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
 * Tester function to check whether the UI schema element is of the expected type.
 *
 * This tester looks at the UI schema, not at the data schema.
 *
 * @param {string} expectedType the expected type of the element, for example 'Group'
 * @return {Function} A tester function for the associated type
 */
function uiTypeIs( expectedType ) {
	return ( uischema ) => !!uischema && uischema.type === expectedType;
}

/**
 * Tester function to check whether the UI schema element asks for the expected control.
 *
 * This tester looks at the UI schema, not at the data schema. The control name comes from the
 * UI schema of the provider, and an extension registers the control that supplies it.
 *
 * @param {string} controlName the expected control name, for example 'WikiLambda.FunctionLookup'
 * @return {Function} A tester function for the associated control
 */
function uiControlIs( controlName ) {
	return ( uischema ) => !!uischema && uischema.control === controlName;
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
	rankRenderer,
	rankWith,
	schemaControlIs,
	schemaTypeIs,
	uiControlIs,
	uiTypeIs,
};
