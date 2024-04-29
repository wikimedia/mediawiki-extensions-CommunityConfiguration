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

function doGetControlTextKeys( propName, schema, data, config ) {
	let keys = [];
	keys.push( mapPropToTextKey( config.i18nTextKeyPrefix, propName, 'label' ) );
	keys.push( mapPropToTextKey( config.i18nTextKeyPrefix, propName, 'control-label' ) );
	keys.push( mapPropToTextKey( config.i18nTextKeyPrefix, propName, 'help-text' ) );
	const newConfig = Object.assign( {}, config, {
		i18nTextKeyPrefix: `${config.i18nTextKeyPrefix}-${propName}`
	} );
	const fallbackData = schema.type === 'array' ? [] : {};
	const propData = ( data && data[ propName ] ) || fallbackData;
	if ( schema.type === 'object' ) {
		for ( const prop in schema.properties ) {
			keys = [
				...keys,
				...doGetControlTextKeys( prop, schema.properties[ prop ], propData, newConfig )
			];
		}
	}
	if ( schema.type === 'array' && !schema.control ) {
		let arrayLabels = propData.map( ( _, index ) =>
			mapPropToTextKey( config.i18nTextKeyPrefix, propName, `${index}-label` )
		);
		if ( schema.items.type === 'object' ) {
			for ( const prop in schema.items.properties ) {
				const arrayItemsFallbackData = schema.type === 'array' ? [] : {};
				const arrayItemsData = ( data && data[ prop ] ) || arrayItemsFallbackData;
				arrayLabels = [
					...arrayLabels,
					...doGetControlTextKeys( prop, schema.items, arrayItemsData, newConfig )
				];
			}

		}
		keys = [ ...keys, ...arrayLabels ];
	}
	if ( schema.enum ) {
		schema.enum.forEach( ( enumValue ) => {
			keys.push( mapPropToTextKey( config.i18nTextKeyPrefix, propName, 'option', enumValue, 'label' ) );
		} );
	}
	return keys;
}

/**
 * Generates all message keys that may be used in the form for the
 * given schema.
 *
 * @param {Object} schema An object representing a JSON schema
 * @param {Object} data The configuration data for the given schema
 * @param {Object} config The editor form configuration
 * @return {Array<string>} An array of message keys
 */
function getControlsTextKeys( schema, data = {}, config = {} ) {
	let keys = [];
	for ( const prop in schema.properties ) {
		const propKeys = doGetControlTextKeys(
			prop, schema.properties[ prop ], data[ prop ], config
		);
		keys = [ ...keys, ...propKeys ];
	}
	return keys;
}

function getEditorTextKeys( schema, data, config ) {
	const titleKey = `${config.i18nTextKeyPrefix}-title`;
	const descKey = `${config.i18nTextKeyPrefix}-description`;
	return [
		titleKey,
		descKey,
		...getControlsTextKeys( schema, data, config )
	];
}

function getControlTextProps( prop, prefix, schema, data ) {
	function getControlTextKeyByType( type ) {
		const textKey = prop.label || mapPropToTextKey( prefix, prop, type );
		// FIXME should probably use some kind of injection to not break in non-MW
		// Messages that can be used here:
		// * communityconfiguration-<provider_name>-<some_field>-label
		// * communityconfiguration-<provider_name>-<some_field>-control-label
		const msg = new mw.Message( mw.messages, textKey );
		return msg.exists() ? msg : null;
	}
	const textProps = {};
	const label = getControlTextKeyByType( 'label' );
	const controlLabel = getControlTextKeyByType( 'control-label' );
	const helpText = getControlTextKeyByType( 'help-text' );
	if ( label ) {
		Object.assign( textProps, { label } );
	}
	if ( controlLabel ) {
		Object.assign( textProps, { controlLabel } );
	}
	if ( helpText ) {
		Object.assign( textProps, { helpText } );
	}

	if ( schema.type === 'array' && data ) {
		const labels = data.map( ( _, index ) => getControlTextKeyByType( `${index}-label` ) );
		Object.assign( textProps, { labels } );
	}

	if ( schema.enum ) {
		const enumLabels = schema.enum.reduce( ( carry, enumValue ) => {
			carry[ enumValue ] = mapPropToTextKey( prefix, prop, 'option', enumValue, 'label' );
			return carry;
		}, {} );
		Object.assign( textProps, { enumLabels } );
	}

	return textProps;
}

module.exports = exports = {
	getEditorTextKeys,
	getControlTextProps
};
