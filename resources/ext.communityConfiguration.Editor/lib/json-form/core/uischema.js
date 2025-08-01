const { getControlTextProps } = require( './i18n.js' );

/**
 * Generates a data structure to hold information about the
 * UI the form should display. Only type object is supported as
 * top level type of the schema.
 *
 * @param {Object} schema An object representing a JSON schema
 * @param {Object} config The editor form configuration
 * @param {string} initialScope The schema parent node defined as a "scope" string
 * @param {Object} data The configuration data for the given schema
 * @return {{ elements: Array<{ name: string, type: string }> }} An array
 * of elements describing the form controls for the properties in the schema.
 */
function buildUISchema( schema, config, initialScope = '', data = {} ) {
	const elements = [];
	for ( const prop in schema.properties ) {
		const scope = initialScope ? `${initialScope}/properties/${prop}` : `#/properties/${prop}`;
		const required = schema.required && schema.required.includes( prop );
		elements.push( buildUISubSchema(
			schema.properties[ prop ], prop, scope, required, config.i18nPrefix,
		) );
	}

	if ( schema.additionalProperties === false ) {
		const dataPropertiesNotInSchema = Object.keys( data ).filter(
			( key ) => !schema.properties[ key ],
		);
		for ( const extraProp of dataPropertiesNotInSchema ) {
			// TODO: consider adding a textual representation of the extra property to the elements
			delete data[ extraProp ];
		}
	}

	return { elements };
}

function buildUISubSchema( subschema, name, scope, required, i18nPrefix ) {
	const defaults = {
		scope,
		name,
		required,
		type: 'Control',
	};

	const additionalProps = Object.assign(
		{}, getControlTextProps( name, i18nPrefix, subschema ),
	);

	return Object.assign( {}, defaults, additionalProps );
}

module.exports = exports = {
	buildUISchema,
	buildUISubSchema,
};
