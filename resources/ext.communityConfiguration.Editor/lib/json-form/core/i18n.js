const { schemaControlIs } = require( './testers.js' );

/**
 * Build a message key from N strings, hyphen separated
 * and lowercased.
 * eg: <prefix>-<infix>-<suffix>
 *
 * @param {...string} fragments
 * @return {string} A tester function for the associated type
 */
function mapPropToTextKey( ...fragments ) {
	// TODO check input params
	return fragments.join( '-' ).toLocaleLowerCase();
}

function camelize( str ) {
	return str.toLowerCase().replace( /[^a-zA-Z0-9]+(.)/g, ( _match, chr ) => chr.toUpperCase() );
}

function getMessageOrNull( key ) {

	const msg = new mw.Message( mw.messages, key );
	return msg.exists() ? msg : null;
}

function getControlMessages( prefix, propName, messageNames ) {
	return messageNames.reduce( ( acc, msgName ) => {
		acc[ camelize( msgName ) ] = getMessageOrNull(
			mapPropToTextKey( prefix, propName, msgName ),
		);
		return acc;
	}, {} );
}

function getStringControlMessages( prefix, propName ) {
	return getControlMessages( prefix, propName, [ 'label', 'help-text', 'placeholder', 'description' ] );
}

function getNumberControlMessages( prefix, propName ) {
	return getControlMessages( prefix, propName, [ 'label', 'help-text', 'placeholder', 'description' ] );
}

function getBooleanControlMessages( prefix, propName ) {
	return getControlMessages( prefix, propName, [ 'label', 'control-label', 'help-text', 'description' ] );
}

function getEnumControlMessages( prefix, propName, enumValues ) {
	const textProps = getControlMessages( prefix, propName, [ 'label', 'help-text', 'description' ] );
	const enumLabels = enumValues.reduce( ( carry, enumValue ) => {
		carry[ enumValue ] = mapPropToTextKey( prefix, propName, 'option', enumValue, 'label' );
		return carry;
	}, {} );
	Object.assign( textProps, { enumLabels } );
	return textProps;
}

function getObjectControlMessages( prefix, propName ) {
	return getControlMessages( prefix, propName, [ 'label', 'help-text', 'description' ] );
}

function getArrayControlMessages( prefix, propName ) {
	return getControlMessages( prefix, propName, [ 'label', 'help-text', 'description', 'item-label', 'add-element-button-label' ] );
}

function getCustomMultiSelectControlMessages( prefix, propName ) {
	return getControlMessages( prefix, propName, [ 'label', 'help-text', 'description' ] );
}

/**
 * Resolve the heading messages of a layout Group.
 *
 * The keys follow the same convention as the control messages:
 * `<prefix>-<label>-section-label` and `<prefix>-<label>-section-description`.
 *
 * @param {string} prefix The i18n prefix, eg 'communityconfiguration-mentorship'
 * @param {string} label The label stem of the group, eg 'eligibility'
 * @return {{ label: (mw.Message|null), description: (mw.Message|null) }}
 */
function getGroupTextProps( prefix, label ) {
	return {
		label: getMessageOrNull( mapPropToTextKey( prefix, label, 'section', 'label' ) ),
		description: getMessageOrNull( mapPropToTextKey( prefix, label, 'section', 'description' ) ),
	};
}

/**
 * Messages for a control that the UI schema selects by name.
 *
 * Such a control can hold any type of data, so there is no way to guess which messages it
 * needs. It gets the full set instead, and a message that does not exist becomes null. A
 * control that needs more can ask for it with the MESSAGES key of the UI schema.
 *
 * @param {string} prefix
 * @param {string} propName
 * @return {Object}
 */
function getCustomControlMessages( prefix, propName ) {
	return getControlMessages( prefix, propName, [
		'label', 'control-label', 'help-text', 'placeholder', 'description',
	] );
}

/**
 * @param {string} prop The property name
 * @param {string} prefix The i18n prefix
 * @param {Object} schema The subschema of the property
 * @param {string|null} uiControl The control the UI schema asks for, if it asks for one
 * @return {Object} The text properties of the control
 */
function getControlTextProps( prop, prefix, schema, uiControl = null ) {
	// The UI schema states which control to use, so there is nothing to guess from the data
	// type. This also keeps the throw below out of reach for a type that the dispatch by data
	// type does not know.
	if ( uiControl ) {
		return getCustomControlMessages( prefix, prop );
	}
	if ( schema.type === 'string' && schema.enum === undefined && schema.control === undefined ) {
		return getStringControlMessages( prefix, prop );
	}
	if ( ( schema.type === 'number' || schema.type === 'integer' ) && schema.enum === undefined ) {
		return getNumberControlMessages( prefix, prop );
	}
	if ( schema.type === 'boolean' ) {
		return getBooleanControlMessages( prefix, prop );
	}
	if ( schema.enum ) {
		return getEnumControlMessages( prefix, prop, schema.enum );
	}
	if ( schema.type === 'object' ) {
		return getObjectControlMessages( prefix, prop );
	}
	if ( schema.type === 'array' && schema.items && schema.items.enum ) {
		return getEnumControlMessages( prefix, prop, schema.items.enum );
	}
	if ( schema.type === 'array' && !schema.control ) {
		return getArrayControlMessages( prefix, prop );
	}
	if (
		schemaControlIs( 'MediaWiki\\Extension\\CommunityConfiguration\\Controls\\PageTitleControl' )( null, schema ) ||
		schemaControlIs( 'MediaWiki\\Extension\\CommunityConfiguration\\Controls\\CommonsFileControl' )( null, schema )
	) {
		return getControlMessages( prefix, prop, [ 'label', 'help-text', 'placeholder', 'description' ] );
	}
	if (
		schemaControlIs( 'MediaWiki\\Extension\\CommunityConfiguration\\Controls\\PageTitlesControl' )( null, schema ) ||
		schemaControlIs( 'MediaWiki\\Extension\\CommunityConfiguration\\Controls\\NamespacesControl' )( null, schema )
	) {
		return getCustomMultiSelectControlMessages( prefix, prop );
	}

	throw new Error( `Prop ${prop}: Unsupported schema type: ${JSON.stringify( schema ) }` );
}

function getLabelsChainRec( schema, pointer, prefix ) {
	const path = pointer.split( '/' );
	const currentLabel = getControlTextProps( path[ 0 ], prefix, schema ).label;
	if ( ( schema.type !== 'object' && schema.type !== 'array' ) || path.length === 1 ) {
		return [ currentLabel ];
	}
	if ( schema.type === 'object' ) {
		const deeperLabels = getLabelsChainRec(
			schema.properties[ path[ 1 ] ],
			path.slice( 1 ).join( '/' ),
			`${prefix}-${path[ 0 ]}`,
		);
		return [ currentLabel, ...deeperLabels ];
	}
	if ( schema.type === 'array' ) {
		const itemLabel = getControlTextProps( path[ 0 ], prefix, schema )
			.itemLabel.params( [ Number.parseInt( path[ 1 ] ) + 1 ] );
		const deeperLabels = getLabelsChainRec(
			schema.items,
			path.slice( 2 ).join( '/' ),
			`${prefix}-${path[ 0 ]}`,
		);
		return [ currentLabel, itemLabel, ...deeperLabels ];
	}
}

/**
 * Note that this reaches getControlTextProps() without a UI schema element, so a property that
 * has a custom control and a type the dispatch by data type does not know still throws here.
 *
 * @param {Object} rootSchema
 * @param {string} pointer
 * @param {string} i18nPrefix
 * @return {mw.Message[]}
 */
function getLabelsChain( rootSchema, pointer, i18nPrefix ) {
	const path = pointer.split( '/' );
	return getLabelsChainRec( rootSchema.properties[ path[ 1 ] ], path.slice( 1 ).join( '/' ), i18nPrefix )
		.filter( ( msg ) => msg );
}

module.exports = exports = {
	getControlTextProps,
	getGroupTextProps,
	getLabelsChain,
};
