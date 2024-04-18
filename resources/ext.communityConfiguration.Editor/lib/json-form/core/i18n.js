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

function getControlsTextKeys( schema, data, config ) {
	let maxDepth = 2;
	const keys = [];
	for ( const prop in schema.properties ) {
		const labelKey = mapPropToTextKey( config.i18nTextKeyPrefix, prop, 'label' );
		keys.push( labelKey );
		keys.push( mapPropToTextKey( config.i18nTextKeyPrefix, prop, 'control-label' ) );
		keys.push( mapPropToTextKey( config.i18nTextKeyPrefix, prop, 'help-text' ) );
		if ( schema.properties[ prop ].type === 'array' && data[ prop ] ) {
			const arrayLabels = data[ prop ].map( ( _, index ) =>
				mapPropToTextKey( config.i18nTextKeyPrefix, prop, `${index}-label` )
			);
			if ( schema.properties[ prop ].items.type === 'object' ) {
				maxDepth--;
				const newConfig = Object.assign( {}, config, {
					i18nTextKeyPrefix: mapPropToTextKey( config.i18nTextKeyPrefix, prop )
				} );
				return keys.concat( arrayLabels ).concat( getControlsTextKeys(
					schema.properties[ prop ].items, data[ prop ][ 0 ], newConfig
				) );
			}
			return keys.concat( arrayLabels );
		}
		if ( schema.properties[ prop ].type === 'object' && maxDepth ) {
			maxDepth--;
			const newConfig = Object.assign( {}, config, {
				i18nTextKeyPrefix: `${config.i18nTextKeyPrefix}-${prop}`
			} );
			return keys.concat(
				getControlsTextKeys( schema.properties[ prop ], data[ prop ], newConfig )
			);
		}
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

	return textProps;
}

module.exports = exports = {
	getEditorTextKeys,
	getControlTextProps
};
