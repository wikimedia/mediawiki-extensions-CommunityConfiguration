function mapPropToTextKey( propName, suffix, prefix ) {
	// TODO check input params
	return `${prefix}-${propName}-${suffix}`.toLocaleLowerCase();
}

function getControlsTextKeys( schema, config ) {
	let maxDepth = 1;
	const keys = [];
	for ( const prop in schema.properties ) {
		keys.push( mapPropToTextKey( prop, 'label', config.i18nTextKeyPrefix ) );
		keys.push( mapPropToTextKey( prop, 'control-label', config.i18nTextKeyPrefix ) );
		keys.push( mapPropToTextKey( prop, 'help-text', config.i18nTextKeyPrefix ) );
		if ( schema.properties[ prop ].type === 'object' && maxDepth ) {
			maxDepth--;
			const newConfig = Object.assign( {}, config, {
				i18nTextKeyPrefix: `${config.i18nTextKeyPrefix}-${prop}`
			} );
			return keys.concat( getControlsTextKeys( schema.properties[ prop ], newConfig ) );
		}
	}
	return keys;
}

function getEditorTextKeys( schema, config ) {
	const titleKey = `${config.i18nTextKeyPrefix}-title`;
	const descKey = `${config.i18nTextKeyPrefix}-description`;
	return [
		titleKey,
		descKey,
		...getControlsTextKeys( schema, config )
	];
}

function getControlTextProps( prop, prefix ) {
	function getControlTextKeyByType( type ) {
		const textKey = prop.label || mapPropToTextKey( prop, type, prefix );
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
	return textProps;
}

module.exports = exports = {
	getEditorTextKeys,
	getControlTextProps
};
