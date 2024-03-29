const { inject } = require( 'vue' );

/**
 * Check wether a given object has a property specified by a key.
 *
 * @param {Object} object The object to lookup in.
 * @param {string} key The property key to lookup for.
 * @return {boolean} Wheter the object contains a property named
 * by "key"
 */
function hasOwn( object, key ) {
	return object ? hasOwnProperty.call( object, key ) : false;
}

/**
 * Check wether a given object has an
 * own property specified by a key.
 *
 * @param {Object} obj The object to update.
 * @param {*} value The value to update the property with
 * @param {string} propPath The path of the target property
 */
const updateObjProp = ( obj, value, propPath ) => {
	const [ head, ...rest ] = propPath.split( '.' );

	if ( !rest.length ) {
		obj[ head ] = value;
	} else {
		if ( !obj[ head ] ) {
			obj[ head ] = {};
		}
		updateObjProp( obj[ head ], value, rest.join( '.' ) );
	}
};

/**
 * Get a valid "empty" value for a given JSON type
 *
 * @param {string} type A valid JSON schema type. One of
 * "string", "number", "boolean", "object", "array".
 * @return {*} A valid "empty" value for the target type
 */
function getDefaultValueForType( type ) {
	switch ( type ) {
		case 'object':
			return {};
		case 'array':
			return [];
		case 'string':
			return '';
		case 'number':
			return null;
		case 'boolean':
			return false;
		default:
			return null;
	}
}

/**
 * Get the form state value of a configuration
 * option by scope or a valid "empty" default.
 *
 * @param {Object} state the form data state object
 * @param {string} scope the path to a given property
 * in the data schema, eg: #/someProp/properties/someNestedProp
 * @param {Object} schema the data schema of the target option
 * @return {*} The value of the target option
 */
function getConfigValueByScope( state, scope, schema ) {
	if ( !scope ) {
		return;
	}
	const valuePath = scope.split( '/properties/' );
	// Drop the inital # from the path
	valuePath.shift();
	const result = valuePath.reduce( ( acc, curr ) => {
		// If a property is informed return whatever value it contains,
		// otherwise fallback to a valid "empty" value for the schema type.
		return hasOwn( acc, curr ) ? acc[ curr ] : getDefaultValueForType( schema.type );
	}, state );
	return result;
}

function setConfigValueByScope( state, scope, newVal ) {
	if ( !scope ) {
		return;
	}
	const valuePath = scope.split( '/properties/' );
	// Drop the inital # from the path
	valuePath.shift();
	// REVIEW: when the user "empties" an input, text or number, newVal
	// will be an empty string "". That's unharmful for strings but
	// creates an schema-invalid state. Should we special case
	// this to pick-up a valid default, eg null? Should we extend this
	// special casing to strings? Or asked in a different way, should
	// editor users have the opportunity to remove a property from a
	// config.
	updateObjProp( state, newVal, valuePath.join( '.' ) );
}

/**
 * Provides bindings for 'Control' elements.
 *
 * Access bindings via the `control` object.
 *
 * @param {Object} props
 * @return {{ control: Object }} Control bindings
 */
function useJsonFormControl( props ) {
	const jsonform = inject( 'jsonform' );

	if ( !jsonform ) {
		throw new Error( "'jsonform' couldn't be injected. Are you within <JsonForm>?" );
	}
	const {
		required,
		scope
	} = props.uischema;
	const modelValue = getConfigValueByScope( jsonform.data, scope, props.schema );
	return {
		control: Object.assign( {}, props, {
			modelValue,
			otherAttrs: {
				required
			}
		} ),
		handleChange( newVal ) {
			setConfigValueByScope( jsonform.data, scope, newVal );
		}
	};
}

/**
 * Provides bindings which can be used for any renderer, so far only 'Control'.
 *
 * Access bindings via the provided 'renderer' object.
 *
 * @param {Object} props
 * @return {{ renderer: Object, rootSchema: Object }} Renderer bindings
 */
const useJsonFormRenderer = ( props ) => {
	const jsonform = inject( 'jsonform' );

	if ( !jsonform ) {
		throw new Error( "'jsonform' couldn't be injected. Are you within <JsonForm>?" );
	}

	const renderer = {
		schema: props.schema.properties[ props.uischema.name ],
		renderers: jsonform.renderers
	};

	return {
		renderer: Object.assign( {}, props, renderer ),
		rootSchema: jsonform.schema
	};
};

module.exports = exports = {
	useJsonFormControl,
	useJsonFormRenderer
};
