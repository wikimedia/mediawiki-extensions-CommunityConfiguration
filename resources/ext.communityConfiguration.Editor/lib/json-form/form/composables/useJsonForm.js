const { inject, computed, ref } = require( 'vue' );
const { buildUISubSchema, extractRef } = require( '../../core/index.js' );
const useValidationErrors = require( './useValidationErrors.js' );

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
 * @param {Object|undefined} definitions any additional definitions the root schema
 * contains in $defs.
 * @return {*} The value of the target option
 */
function getConfigValueByScope( state, scope, schema, definitions = {} ) {
	if ( !scope ) {
		return;
	}
	const valuePath = scope.replace( /\/properties/g, '' ).split( '/' );
	// Drop the inital # from the path
	valuePath.shift();
	const result = valuePath.reduce( ( acc, curr ) => {
		// If a property is informed return whatever value it contains,
		// otherwise fallback to a valid "empty" value for the schema type.
		if ( hasOwn( acc, curr ) ) {
			return acc[ curr ];
		} else if ( schema.type ) {
			return getDefaultValueForType( schema.type );
		} else if ( schema.$ref ) {
			// Should never get here when used with CommunityConfiguration2.0 schemas
			// which do not contain references
			const refDefinition = definitions[ extractRef( schema.$ref ) ];
			return getDefaultValueForType( refDefinition.type );
		}
		return null;
	}, state );
	return result;
}

function setConfigValueByScope( state, scope, newVal ) {
	if ( !scope ) {
		return;
	}
	const valuePath = scope.replace( /\/properties/g, '' ).split( '/' );
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

function scopeToJsonPointer( scope ) {
	return scope
		.replace( '#/properties/', '' )
		.replace( '/properties/', '.' )
		.replace( '/', '.' );
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
		scope,
	} = props.uischema;
	const modelValue = ref(
		getConfigValueByScope( jsonform.data, scope, props.schema, jsonform.schema.$defs )
	);
	const pointer = scopeToJsonPointer( scope );
	const otherAttrs = { required };

	const { getValidationErrorMessageForFormFieldId } = useValidationErrors();
	const statusMessages = computed( () => {
		const validationErrorMessage = getValidationErrorMessageForFormFieldId( pointer );
		if ( validationErrorMessage ) {
			return { error: validationErrorMessage };
		}
		return {};
	} );

	return {
		control: Object.assign( {}, props, {
			modelValue,
			pointer,
			statusMessages,
			otherAttrs,
		} ),
		handleChange( newVal ) {
			setConfigValueByScope( jsonform.data, scope, newVal );
		},
	};
}

/**
 * Provides bindings for 'ArrayControl' elements.
 *
 * Access bindings via the `control` object.
 *
 * @param {Object} props
 * @return {{ control: Object, childUISchema: Object }} Control bindings
 */
function useJsonFormArrayControl( props ) {
	const jsonform = inject( 'jsonform' );
	const i18n = inject( 'i18n' );

	if ( !jsonform ) {
		throw new Error( "'jsonform' couldn't be injected. Are you within <JsonForm>?" );
	}
	const {
		required,
		scope,
	} = props.uischema;

	const modelValue = computed(
		() => getConfigValueByScope( jsonform.data, scope, props.schema, jsonform.schema.$defs )
	);
	const pointer = scopeToJsonPointer( scope );
	const {
		getValidationErrorMessageForFormFieldId,
		adjustValidationErrorsOnArrayItemDelete,
	} = useValidationErrors();
	const statusMessages = computed( () => {
		const validationErrorMessage = getValidationErrorMessageForFormFieldId( pointer );
		if ( validationErrorMessage ) {
			return { error: validationErrorMessage };
		}
		return {};
	} );

	// Treat all array children as if they are simple controls,
	// if they array items are objects or arrays the appropriate
	// renderer will be dispatched to inspect the nested subschemas.
	const childUISchema = buildUISubSchema(
		props.schema,
		props.uischema.name,
		props.uischema.scope,
		// TODO determine if an array item is required based on min/maxItems, T358659
		false,
		jsonform.config.i18nPrefix
	);

	function indexedChildUISchema( index ) {
		return Object.assign( {}, childUISchema, {
			scope: `${childUISchema.scope}/${index}`,
			label: childUISchema.itemLabel ?
				i18n( childUISchema.itemLabel.key, [ index + 1 ] ) :
				null,
		} );
	}
	return {
		indexedChildUISchema,
		data: modelValue,
		control: Object.assign( {}, props, {
			modelValue,
			pointer,
			statusMessages,
			otherAttrs: {
				required,
			},
		} ),
		addEmptyElement() {
			const emptyValue = getDefaultValueForType( props.schema.items.type );
			setConfigValueByScope( jsonform.data, scope, [ ...modelValue.value, emptyValue ] );
		},
		removeElement( index ) {
			setConfigValueByScope( jsonform.data, scope, [
				...modelValue.value.slice( 0, index ),
				...modelValue.value.slice( index + 1 ),
			] );
			adjustValidationErrorsOnArrayItemDelete( pointer, index );
		},
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
	let schema = props.schema;
	if ( props.schema.type === 'array' ) {
		schema = schema.items;
	}
	if ( props.schema.type === 'object' ) {
		schema = props.schema.properties[ props.uischema.name ];
	}
	const renderer = {
		schema,
		renderers: jsonform.renderers,
	};

	return {
		renderer: Object.assign( {}, props, renderer ),
		rootSchema: jsonform.schema,
	};
};

module.exports = exports = {
	useJsonFormArrayControl,
	useJsonFormControl,
	useJsonFormRenderer,
};
