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

function getMessageOrNull( key ) {
	// eslint-disable-next-line mediawiki/msg-doc
	const msg = new mw.Message( mw.messages, key );
	return msg.exists() ? msg : null;
}

function getStringControlMessages( prefix, propName, asMessageObject ) {
	const labelKey = mapPropToTextKey( prefix, propName, 'label' );
	const helpTextLabelKey = mapPropToTextKey( prefix, propName, 'help-text' );
	const placeholderKey = mapPropToTextKey( prefix, propName, 'placeholder' );
	if ( asMessageObject ) {
		return {
			label: getMessageOrNull( labelKey ),
			helpText: getMessageOrNull( helpTextLabelKey ),
			placeholder: getMessageOrNull( placeholderKey )
		};
	}
	return [
		labelKey,
		helpTextLabelKey,
		placeholderKey
	];
}

function getNumberControlMessages( prefix, propName, asMessageObject ) {
	const labelKey = mapPropToTextKey( prefix, propName, 'label' );
	const helpTextLabelKey = mapPropToTextKey( prefix, propName, 'help-text' );
	const placeholderKey = mapPropToTextKey( prefix, propName, 'placeholder' );
	if ( asMessageObject ) {
		return {
			label: getMessageOrNull( labelKey ),
			helpText: getMessageOrNull( helpTextLabelKey ),
			placeholder: getMessageOrNull( placeholderKey )
		};
	}
	return [
		labelKey,
		helpTextLabelKey,
		placeholderKey
	];
}

function getBooleanControlMessages( prefix, propName, asMessageObject ) {
	const labelKey = mapPropToTextKey( prefix, propName, 'label' );
	const controlLabelKey = mapPropToTextKey( prefix, propName, 'control-label' );
	const helpTextLabelKey = mapPropToTextKey( prefix, propName, 'help-text' );
	if ( asMessageObject ) {
		return {
			label: getMessageOrNull( labelKey ),
			controlLabel: getMessageOrNull( controlLabelKey ),
			helpText: getMessageOrNull( helpTextLabelKey )
		};
	}
	return [
		labelKey,
		controlLabelKey,
		helpTextLabelKey
	];
}

function getEnumControlMessages( prefix, propName, enumValues, asMessageObject ) {
	const labelKey = mapPropToTextKey( prefix, propName, 'label' );
	const helpTextKey = mapPropToTextKey( prefix, propName, 'help-text' );
	const enumLabels = enumValues.reduce( ( carry, enumValue ) => {
		carry[ enumValue ] = mapPropToTextKey( prefix, propName, 'option', enumValue, 'label' );
		return carry;
	}, {} );
	if ( asMessageObject ) {
		const textProps = {
			label: getMessageOrNull( labelKey ),
			helpText: getMessageOrNull( helpTextKey )
		};
		Object.assign( textProps, { enumLabels } );
		return textProps;
	}
	return [
		labelKey,
		helpTextKey,
		// eslint-disable-next-line es-x/no-object-values
		...Object.values( enumLabels )
	];
}

function getObjectControlMessages( prefix, propName, objectProperties, asMessageObject ) {
	const labelKey = mapPropToTextKey( prefix, propName, 'label' );
	const helpTextLabelKey = mapPropToTextKey( prefix, propName, 'help-text' );

	if ( asMessageObject ) {
		return {
			label: getMessageOrNull( labelKey ),
			helpText: getMessageOrNull( helpTextLabelKey )
		};
	}

	const newConfig = { i18nTextKeyPrefix: `${ prefix }-${ propName }` };
	let keys = [
		labelKey,
		helpTextLabelKey
	];
	for ( const prop in objectProperties ) {
		keys = [
			...keys,
			...doGetControlTextKeys( prop, objectProperties[ prop ], newConfig )
		];
	}
	return keys;
}

function getArrayControlMessages( prefix, propName, arrayItems, asMessageObject ) {
	const labelKey = mapPropToTextKey( prefix, propName, 'label' );
	const helpTextLabelKey = mapPropToTextKey( prefix, propName, 'help-text' );
	const itemLabelKey = mapPropToTextKey( prefix, propName, 'item-label' );
	const addElementButtonLabelKey = mapPropToTextKey( prefix, propName, 'add-element-button-label' );

	if ( asMessageObject ) {
		return {
			label: getMessageOrNull( labelKey ),
			helpText: getMessageOrNull( helpTextLabelKey ),
			itemLabel: getMessageOrNull( itemLabelKey ),
			addElementButtonLabel: getMessageOrNull( addElementButtonLabelKey )
		};
	}

	let arrayLabels = [
		labelKey,
		helpTextLabelKey,
		itemLabelKey,
		addElementButtonLabelKey
	];
	if ( arrayItems.type === 'object' ) {
		const newConfig = { i18nTextKeyPrefix: `${ prefix }-${ propName }` };
		for ( const prop in arrayItems.properties ) {
			arrayLabels = [
				...arrayLabels,
				...doGetControlTextKeys(
					prop,
					arrayItems.properties[ prop ],
					newConfig
				)
			];
		}
	}

	return arrayLabels;
}

function getCustomMultiSelectControlMessages( prefix, propName, asMessageObject ) {
	const labelKey = mapPropToTextKey( prefix, propName, 'label' );
	const helpTextLabelKey = mapPropToTextKey( prefix, propName, 'help-text' );
	if ( asMessageObject ) {
		return {
			label: getMessageOrNull( labelKey ),
			helpText: getMessageOrNull( helpTextLabelKey )
		};
	}
	return [
		labelKey,
		helpTextLabelKey,
		'mw-widgets-titlesmultiselect-placeholder',
		// TODO can be removed after T345386 is resolved
		'communityconfiguration-editor-chip-control-aria-chip-description'
	];
}

function getCustomPageTitleControlMessages( prefix, propName, asMessageObject ) {
	const labelKey = mapPropToTextKey( prefix, propName, 'label' );
	const helpTextLabelKey = mapPropToTextKey( prefix, propName, 'help-text' );
	const placeholderKey = mapPropToTextKey( prefix, propName, 'placeholder' );
	const noResultsKey = 'communityconfiguration-page-title-control-no-results';
	if ( asMessageObject ) {
		return {
			label: getMessageOrNull( labelKey ),
			helpText: getMessageOrNull( helpTextLabelKey ),
			placeholder: getMessageOrNull( placeholderKey )
		};
	}
	return [
		labelKey,
		helpTextLabelKey,
		placeholderKey,
		noResultsKey
	];
}

function doGetControlTextKeys( propName, schema, config ) {
	if ( schema.type === 'string' && schema.enum === undefined && schema.control === undefined ) {
		/* eslint-disable-next-line es-x/no-object-values */
		return Object.values( getStringControlMessages( config.i18nTextKeyPrefix, propName ) );
	}
	if ( ( schema.type === 'number' || schema.type === 'integer' ) && schema.enum === undefined ) {
		/* eslint-disable-next-line es-x/no-object-values */
		return Object.values( getNumberControlMessages( config.i18nTextKeyPrefix, propName ) );
	}
	if ( schema.type === 'boolean' ) {
		return getBooleanControlMessages( config.i18nTextKeyPrefix, propName );
	}
	if ( schema.enum ) {
		return getEnumControlMessages( config.i18nTextKeyPrefix, propName, schema.enum );
	}
	if ( schema.type === 'object' ) {
		return getObjectControlMessages(
			config.i18nTextKeyPrefix,
			propName,
			schema.properties
		);
	}
	if ( schema.type === 'array' && !schema.control ) {
		return getArrayControlMessages(
			config.i18nTextKeyPrefix,
			propName,
			schema.items
		);
	}
	if ( schemaControlIs( 'MediaWiki\\Extension\\CommunityConfiguration\\Controls\\PageTitleControl' )( null, schema ) ) {
		return getCustomPageTitleControlMessages( config.i18nTextKeyPrefix, propName );
	}
	if (
		schemaControlIs( 'MediaWiki\\Extension\\CommunityConfiguration\\Controls\\PageTitlesControl' )( null, schema ) ||
		schemaControlIs( 'MediaWiki\\Extension\\CommunityConfiguration\\Controls\\NamespacesControl' )( null, schema )
	) {
		return getCustomMultiSelectControlMessages( config.i18nTextKeyPrefix, propName );
	}

	throw new Error( `Prop ${ propName }: Unsupported schema type: ${ JSON.stringify( schema ) }` );
}

function getControlTextProps( prop, prefix, schema ) {
	if ( schema.type === 'string' && schema.enum === undefined && schema.control === undefined ) {
		return getStringControlMessages( prefix, prop, true );
	}
	if ( ( schema.type === 'number' || schema.type === 'integer' ) && schema.enum === undefined ) {
		return getNumberControlMessages( prefix, prop, true );
	}
	if ( schema.type === 'boolean' ) {
		return getBooleanControlMessages( prefix, prop, true );
	}
	if ( schema.enum ) {
		return getEnumControlMessages( prefix, prop, schema.enum, true );
	}
	if ( schema.type === 'object' ) {
		return getObjectControlMessages( prefix, prop, schema.properties, true );
	}
	if ( schema.type === 'array' && !schema.control ) {
		return getArrayControlMessages( prefix, prop, schema.items, true );
	}
	if ( schemaControlIs( 'MediaWiki\\Extension\\CommunityConfiguration\\Controls\\PageTitleControl' )( null, schema ) ) {
		return getCustomPageTitleControlMessages( prefix, prop, true );
	}
	if (
		schemaControlIs( 'MediaWiki\\Extension\\CommunityConfiguration\\Controls\\PageTitlesControl' )( null, schema ) ||
		schemaControlIs( 'MediaWiki\\Extension\\CommunityConfiguration\\Controls\\NamespacesControl' )( null, schema )
	) {
		return getCustomMultiSelectControlMessages( prefix, prop, true );
	}

	throw new Error( `Prop ${prop}: Unsupported schema type: ${JSON.stringify( schema ) }` );
}

module.exports = exports = {
	getControlTextProps
};
