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

function getStringControlMessages( prefix, propName ) {
	const labelKey = mapPropToTextKey( prefix, propName, 'label' );
	const helpTextLabelKey = mapPropToTextKey( prefix, propName, 'help-text' );
	const placeholderKey = mapPropToTextKey( prefix, propName, 'placeholder' );

	return {
		label: getMessageOrNull( labelKey ),
		helpText: getMessageOrNull( helpTextLabelKey ),
		placeholder: getMessageOrNull( placeholderKey )
	};
}

function getNumberControlMessages( prefix, propName ) {
	const labelKey = mapPropToTextKey( prefix, propName, 'label' );
	const helpTextLabelKey = mapPropToTextKey( prefix, propName, 'help-text' );
	const placeholderKey = mapPropToTextKey( prefix, propName, 'placeholder' );

	return {
		label: getMessageOrNull( labelKey ),
		helpText: getMessageOrNull( helpTextLabelKey ),
		placeholder: getMessageOrNull( placeholderKey )
	};
}

function getBooleanControlMessages( prefix, propName ) {
	const labelKey = mapPropToTextKey( prefix, propName, 'label' );
	const controlLabelKey = mapPropToTextKey( prefix, propName, 'control-label' );
	const helpTextLabelKey = mapPropToTextKey( prefix, propName, 'help-text' );

	return {
		label: getMessageOrNull( labelKey ),
		controlLabel: getMessageOrNull( controlLabelKey ),
		helpText: getMessageOrNull( helpTextLabelKey )
	};
}

function getEnumControlMessages( prefix, propName, enumValues ) {
	const labelKey = mapPropToTextKey( prefix, propName, 'label' );
	const helpTextKey = mapPropToTextKey( prefix, propName, 'help-text' );
	const enumLabels = enumValues.reduce( ( carry, enumValue ) => {
		carry[ enumValue ] = mapPropToTextKey( prefix, propName, 'option', enumValue, 'label' );
		return carry;
	}, {} );

	const textProps = {
		label: getMessageOrNull( labelKey ),
		helpText: getMessageOrNull( helpTextKey )
	};
	Object.assign( textProps, { enumLabels } );
	return textProps;
}

function getObjectControlMessages( prefix, propName ) {
	const labelKey = mapPropToTextKey( prefix, propName, 'label' );
	const helpTextLabelKey = mapPropToTextKey( prefix, propName, 'help-text' );

	return {
		label: getMessageOrNull( labelKey ),
		helpText: getMessageOrNull( helpTextLabelKey )
	};
}

function getArrayControlMessages( prefix, propName ) {
	const labelKey = mapPropToTextKey( prefix, propName, 'label' );
	const helpTextLabelKey = mapPropToTextKey( prefix, propName, 'help-text' );
	const itemLabelKey = mapPropToTextKey( prefix, propName, 'item-label' );
	const addElementButtonLabelKey = mapPropToTextKey( prefix, propName, 'add-element-button-label' );

	return {
		label: getMessageOrNull( labelKey ),
		helpText: getMessageOrNull( helpTextLabelKey ),
		itemLabel: getMessageOrNull( itemLabelKey ),
		addElementButtonLabel: getMessageOrNull( addElementButtonLabelKey )
	};
}

function getCustomMultiSelectControlMessages( prefix, propName ) {
	const labelKey = mapPropToTextKey( prefix, propName, 'label' );
	const helpTextLabelKey = mapPropToTextKey( prefix, propName, 'help-text' );

	return {
		label: getMessageOrNull( labelKey ),
		helpText: getMessageOrNull( helpTextLabelKey )
	};
}

function getCustomPageTitleControlMessages( prefix, propName ) {
	const labelKey = mapPropToTextKey( prefix, propName, 'label' );
	const helpTextLabelKey = mapPropToTextKey( prefix, propName, 'help-text' );
	const placeholderKey = mapPropToTextKey( prefix, propName, 'placeholder' );

	return {
		label: getMessageOrNull( labelKey ),
		helpText: getMessageOrNull( helpTextLabelKey ),
		placeholder: getMessageOrNull( placeholderKey )
	};
}

function getControlTextProps( prop, prefix, schema ) {
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
	if ( schema.type === 'array' && !schema.control ) {
		return getArrayControlMessages( prefix, prop );
	}
	if ( schemaControlIs( 'MediaWiki\\Extension\\CommunityConfiguration\\Controls\\PageTitleControl' )( null, schema ) ) {
		return getCustomPageTitleControlMessages( prefix, prop );
	}
	if (
		schemaControlIs( 'MediaWiki\\Extension\\CommunityConfiguration\\Controls\\PageTitlesControl' )( null, schema ) ||
		schemaControlIs( 'MediaWiki\\Extension\\CommunityConfiguration\\Controls\\NamespacesControl' )( null, schema )
	) {
		return getCustomMultiSelectControlMessages( prefix, prop );
	}

	throw new Error( `Prop ${prop}: Unsupported schema type: ${JSON.stringify( schema ) }` );
}

module.exports = exports = {
	getControlTextProps
};
