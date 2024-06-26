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
	// eslint-disable-next-line mediawiki/msg-doc
	const msg = new mw.Message( mw.messages, key );
	return msg.exists() ? msg : null;
}

function getControlMessages( prefix, propName, messageNames ) {
	return messageNames.reduce( ( acc, msgName ) => {
		acc[ camelize( msgName ) ] = getMessageOrNull(
			mapPropToTextKey( prefix, propName, msgName )
		);
		return acc;
	}, {} );
}

function getStringControlMessages( prefix, propName ) {
	return getControlMessages( prefix, propName, [ 'label', 'help-text', 'placeholder' ] );
}

function getNumberControlMessages( prefix, propName ) {
	return getControlMessages( prefix, propName, [ 'label', 'help-text', 'placeholder' ] );
}

function getBooleanControlMessages( prefix, propName ) {
	return getControlMessages( prefix, propName, [ 'label', 'control-label', 'help-text' ] );
}

function getEnumControlMessages( prefix, propName, enumValues ) {
	const textProps = getControlMessages( prefix, propName, [ 'label', 'help-text' ] );
	const enumLabels = enumValues.reduce( ( carry, enumValue ) => {
		carry[ enumValue ] = mapPropToTextKey( prefix, propName, 'option', enumValue, 'label' );
		return carry;
	}, {} );
	Object.assign( textProps, { enumLabels } );
	return textProps;
}

function getObjectControlMessages( prefix, propName ) {
	return getControlMessages( prefix, propName, [ 'label', 'help-text' ] );
}

function getArrayControlMessages( prefix, propName ) {
	return getControlMessages( prefix, propName, [ 'label', 'help-text', 'item-label', 'add-element-button-label' ] );
}

function getCustomMultiSelectControlMessages( prefix, propName ) {
	return getControlMessages( prefix, propName, [ 'label', 'help-text' ] );
}

function getCustomPageTitleControlMessages( prefix, propName ) {
	return getControlMessages( prefix, propName, [ 'label', 'help-text', 'placeholder' ] );
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
