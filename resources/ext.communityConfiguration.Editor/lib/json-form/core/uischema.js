const { getControlTextProps, getGroupTextProps } = require( './i18n.js' );

const TYPE_CONTROL = 'Control';
const TYPE_GROUP = 'Group';

/**
 * Generates a data structure to hold information about the
 * UI the form should display. Only type object is supported as
 * top level type of the schema.
 *
 * Without a layout, the form shows one control for every property of the schema, in the order
 * of the schema. With a layout, the form follows the order and the grouping of the layout. A
 * property that the layout does not place still shows, at the end, because no configurable
 * value may disappear from the form.
 *
 * The layout is a separate parameter on purpose. It must not travel inside $config, because
 * ObjectControl passes a copy of $config to a nested call, and the layout of the form cannot
 * describe the subschema of a nested object.
 *
 * @param {Object} schema An object representing a JSON schema
 * @param {Object} config The editor form configuration
 * @param {string} initialScope The schema parent node defined as a "scope" string
 * @param {Object} data The configuration data for the given schema
 * @param {Object|null} uiSchema The layout, shaped as `{ elements: [ ... ] }`. It applies to
 * the top level of the form only, so it is ignored when $initialScope is not empty.
 * @return {{ elements: Array<{ name: string, type: string }> }} An array
 * of elements describing the form controls for the properties in the schema.
 */
function buildUISchema( schema, config, initialScope = '', data = {}, uiSchema = null ) {
	if ( schema.additionalProperties === false ) {
		const dataPropertiesNotInSchema = Object.keys( data ).filter(
			( key ) => !schema.properties[ key ],
		);
		for ( const extraProp of dataPropertiesNotInSchema ) {
			// TODO: consider adding a textual representation of the extra property to the elements
			delete data[ extraProp ];
		}
	}

	if ( initialScope === '' && uiSchema && Array.isArray( uiSchema.elements ) ) {
		return { elements: buildLayoutElements( schema, config, uiSchema ) };
	}

	const elements = [];
	for ( const prop in schema.properties ) {
		const scope = initialScope ? `${initialScope}/properties/${prop}` : `#/properties/${prop}`;
		const required = schema.required && schema.required.includes( prop );
		elements.push( buildUISubSchema(
			schema.properties[ prop ], prop, scope, required, config.i18nPrefix,
		) );
	}

	return { elements };
}

/**
 * Build the elements of the form from a layout.
 *
 * @param {Object} schema The root data schema
 * @param {Object} config The editor form configuration
 * @param {Object} uiSchema The layout, shaped as `{ elements: [ ... ] }`
 * @return {Array<Object>} The elements to render
 */
function buildLayoutElements( schema, config, uiSchema ) {
	const usedProps = new Set();
	const elements = buildElements( schema, config, uiSchema.elements, usedProps );

	// A property that the layout does not place is added at the end. Without this, the editor
	// would show a form that cannot set that property, and would then save a configuration
	// without it.
	for ( const prop in schema.properties ) {
		if ( !usedProps.has( prop ) ) {
			const required = schema.required && schema.required.includes( prop );
			elements.push( buildUISubSchema(
				schema.properties[ prop ], prop, `#/properties/${prop}`, required, config.i18nPrefix,
			) );
		}
	}

	return elements;
}

/**
 * @param {Object} schema The root data schema
 * @param {Object} config The editor form configuration
 * @param {Array|undefined} elements The elements of the layout, or of one Group of it
 * @param {Set<string>} usedProps Names of the properties that already have an element. A
 * property goes in here only when an element for it really goes into the form, so that the
 * safety net in buildLayoutElements() still sees the properties that this function skips.
 * @return {Array<Object>} The elements to render
 */
function buildElements( schema, config, elements, usedProps ) {
	const built = [];
	for ( const element of elements || [] ) {
		if ( typeof element === 'string' ) {
			const control = controlForScope( schema, config, element, usedProps );
			if ( control ) {
				built.push( control );
			}
			continue;
		}
		if ( !element || typeof element !== 'object' ) {
			mw.log.warn( 'json-form: the UI schema holds an element that is not a scope or an object.' );
			continue;
		}
		const type = element.type || TYPE_CONTROL;
		if ( type === TYPE_GROUP ) {
			built.push( buildGroup( schema, config, element, usedProps ) );
			continue;
		}
		if ( type === TYPE_CONTROL ) {
			const control = controlForScope( schema, config, element.scope, usedProps, element );
			if ( control ) {
				built.push( control );
			}
			continue;
		}
		// This version of the editor does not know this type of element. Skip it without
		// marking any property used, so that the safety net still shows the property.
		mw.log.warn( `json-form: the UI schema uses the unknown element type "${type}".` );
	}
	return built;
}

/**
 * @param {Object} schema The root data schema
 * @param {Object} config The editor form configuration
 * @param {Object} element A Group element of the layout
 * @param {Set<string>} usedProps
 * @return {Object} The Group element to render
 */
function buildGroup( schema, config, element, usedProps ) {
	const label = typeof element.label === 'string' ? element.label : null;
	return Object.assign(
		{
			type: TYPE_GROUP,
			name: label,
			elements: buildElements( schema, config, element.elements, usedProps ),
		},
		// A group without a label is a plain sequence of controls, with no heading.
		label === null ?
			{ label: null, description: null } :
			getGroupTextProps( config.i18nPrefix, label ),
	);
}

/**
 * Build the control element for one scope of the layout.
 *
 * @param {Object} schema The root data schema
 * @param {Object} config The editor form configuration
 * @param {string} scope A scope string, for example `#/properties/Foo`
 * @param {Set<string>} usedProps
 * @param {Object|null} uiElement The Control element that holds the scope, if there is one
 * @return {Object|null} The element to render, or null when the scope is unusable
 */
function controlForScope( schema, config, scope, usedProps, uiElement = null ) {
	const prop = topLevelPropFromScope( scope );
	if ( prop === null ) {
		// This iteration places top level properties only.
		mw.log.warn( `json-form: the UI schema scope "${scope}" is not a top level property.` );
		return null;
	}
	if ( !schema.properties || !schema.properties[ prop ] ) {
		mw.log.warn( `json-form: the UI schema scope "${scope}" is not in the data schema.` );
		return null;
	}
	if ( usedProps.has( prop ) ) {
		// Two controls for one property would both write the same value.
		mw.log.warn( `json-form: the UI schema places "${scope}" more than once.` );
		return null;
	}
	usedProps.add( prop );
	const required = schema.required && schema.required.includes( prop );
	return buildUISubSchema(
		schema.properties[ prop ], prop, scope, required, config.i18nPrefix, uiElement,
	);
}

/**
 * Extract the property name from a top level scope string.
 *
 * @param {string} scope
 * @return {string|null} The property name, or null when the scope does not name a top level
 * property of the data schema
 */
function topLevelPropFromScope( scope ) {
	if ( typeof scope !== 'string' ) {
		return null;
	}
	const match = /^#\/properties\/([^/]+)$/.exec( scope );
	return match ? match[ 1 ] : null;
}

/**
 * @param {Object} subschema
 * @param {string} name
 * @param {string} scope
 * @param {boolean} required
 * @param {string} i18nPrefix
 * @param {Object|null} uiElement The Control element of the layout, if the layout has one for
 * this property. Its control name and its options go onto the result.
 * @return {Object} The element to render
 */
function buildUISubSchema( subschema, name, scope, required, i18nPrefix, uiElement = null ) {
	const control = uiElement && typeof uiElement.control === 'string' ? uiElement.control : null;
	const defaults = {
		scope,
		name,
		required,
		type: TYPE_CONTROL,
	};

	const additionalProps = Object.assign(
		{}, getControlTextProps( name, i18nPrefix, subschema, control ),
	);

	if ( control !== null ) {
		additionalProps.control = control;
		// The control decides what its options mean. They hold presentation settings only,
		// because everything about which values are valid stays in the data schema.
		additionalProps.options = uiElement.options || {};
	}

	return Object.assign( {}, defaults, additionalProps );
}

module.exports = exports = {
	buildUISchema,
	buildUISubSchema,
};
