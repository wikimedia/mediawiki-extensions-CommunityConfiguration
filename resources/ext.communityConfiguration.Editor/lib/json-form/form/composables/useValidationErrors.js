const { reactive, inject } = require( 'vue' );
const { getLabelsChain } = require( '../../core/i18n.js' );

/**
 * @typedef {Object} Schema
 * @property {string} type
 */

/**
 * @typedef {Object} ValidationError
 * @property {mw.Message[]} formFieldLabels
 * @property {string} formFieldId
 * @property {string} messageLiteral
 */

/** @type { { validationErrors: ValidationError[] } } */
const state = reactive( {
	validationErrors: []
} );

/**
 * @typedef {Object} CommunityConfiguration_ValidationErrorStore
 * @property {clearValidationErrors} clearValidationErrors
 * @property {getValidationErrorMessageForFormFieldId} getValidationErrorMessageForFormFieldId
 * @property {getAllValidationErrors} getAllValidationErrors
 * @property {setValidationErrorsFromSubmitResponse} setValidationErrorsFromSubmitResponse
 * @property {adjustValidationErrorsOnArrayItemDelete} adjustValidationErrorsOnArrayItemDelete
 * @property {setValidationErrorForFieldId} setValidationErrorForFieldId
 * @property {clearValidationErrorForFieldId} clearValidationErrorForFieldId
 */

/**
 * If this composable is not used inside a <JsonForm>,
 * then the root schema and i18n prefix must be provided.
 *
 * @param {Object} [schemaAndPrefix]
 * @param {Schema} schemaAndPrefix.schema
 * @param {Object} schemaAndPrefix.config
 * @param {string} schemaAndPrefix.config.i18nPrefix
 *
 * @return {CommunityConfiguration_ValidationErrorStore}
 */
module.exports = exports = ( schemaAndPrefix ) => {
	const jsonform = inject( 'jsonform', schemaAndPrefix );
	if ( !jsonform ) {
		throw new Error( "'jsonform' couldn't be injected. Are you within <JsonForm>? Alternatively, inject object with schema and config.i18nPrefix keys" );
	}
	const rootSchema = jsonform.schema;
	const i18nPrefix = jsonform.config.i18nPrefix;

	/**
	 * @callback getAllValidationErrors
	 * @return {ValidationError[]}
	 */
	function getAllValidationErrors() {
		return state.validationErrors;
	}

	/**
	 * @callback getValidationErrorMessageForFormFieldId
	 * @param {string} pointer
	 * @return {?string} validation error message literal
	 */
	function getValidationErrorMessageForFormFieldId( pointer ) {
		const validationError = state.validationErrors.find(
			( error ) => error.formFieldId === pointer
		);

		return validationError ? validationError.messageLiteral : null;
	}

	/**
	 * @callback clearValidationErrors
	 * @return {void}
	 */
	function clearValidationErrors() {
		state.validationErrors = [];
	}

	/**
	 * @callback adjustValidationErrorsOnArrayItemDelete
	 * @param {string} pointerToArray
	 * @param {number} indexOfDeletedItem
	 */
	function adjustValidationErrorsOnArrayItemDelete( pointerToArray, indexOfDeletedItem ) {
		removeDeletedArrayItemErrors( pointerToArray, indexOfDeletedItem );
		decrementArrayItemErrorIndexes( pointerToArray, indexOfDeletedItem );
	}

	/**
	 * @callback setValidationErrorsFromSubmitResponse
	 * @param {Object.<string, unknown>} response
	 * @return {void}
	 */
	function setValidationErrorsFromSubmitResponse( response ) {
		if ( !isValidationErrorResponse( response ) ) {
			return;
		}

		state.validationErrors = response.errors.map( ( error ) => {
			const adjustedPointer = adjustPointerForValidationErrors(
				rootSchema,
				error.data.pointer
			);
			const labels = getLabelsChain(
				rootSchema,
				adjustedPointer,
				i18nPrefix
			);
			const formFieldId = adjustedPointer
				.slice( 1 ) // Remove leading '/'
				.replace( /\//g, '.' );
			return Object.assign( {}, error.data, {
				formFieldLabels: labels,
				formFieldId
			} );
		} );
	}

	/**
	 * @callback setValidationErrorForFieldId
	 * @param {string} fieldId
	 * @param {string} messageLiteral
	 */
	function setValidationErrorForFieldId( fieldId, messageLiteral ) {
		state.validationErrors = state.validationErrors.filter(
			( error ) => error.formFieldId !== fieldId
		);
		const adjustedPointer = '/' + fieldId
			.replace( /\./g, '/' );

		const labels = getLabelsChain(
			rootSchema,
			adjustedPointer,
			i18nPrefix
		);

		state.validationErrors.push( {
			formFieldId: fieldId,
			formFieldLabels: labels,
			messageLiteral
		} );
	}

	/**
	 * @callback clearValidationErrorForFieldId
	 * @param {string} fieldId
	 * @return {void}
	 */
	function clearValidationErrorForFieldId( fieldId ) {
		state.validationErrors = state.validationErrors.filter(
			( error ) => error.formFieldId !== fieldId
		);
	}

	return {
		clearValidationErrors,
		setValidationErrorsFromSubmitResponse,
		getAllValidationErrors,
		getValidationErrorMessageForFormFieldId,
		adjustValidationErrorsOnArrayItemDelete,
		setValidationErrorForFieldId,
		clearValidationErrorForFieldId
	};
};

// region private methods

function isValidationErrorResponse( response ) {
	if ( !response.errors || !Array.isArray( response.errors ) ) {
		return false;
	}
	const errorMessageCodes = response.errors.map( ( err ) => err.code );
	return errorMessageCodes.every( ( code ) => code === 'communityconfiguration-schema-validation-error' );
}

/**
 * Some arrays are rendered as a single control with their items not directly addressable.
 * In that case the pointer targeting an item needs to be retargeted to the parent array instead.
 *
 * @param {Object} rootSchema
 * @param {string} pointer
 * @return {string}
 */
function adjustPointerForValidationErrors( rootSchema, pointer ) {
	/**
	 * @param {string} pointerToAdjust
	 * @return {string}
	 */
	function ensureTargetIsArrayNotItem( pointerToAdjust ) {
		const parts = pointerToAdjust.split( '/' );
		if ( Number.isInteger( Number.parseInt( parts.pop() ) ) ) {
			return parts.join( '/' );
		}
		return pointerToAdjust;
	}

	const path = pointer.split( '/' ).slice( 1 );
	let subSchema = rootSchema;
	for ( const p of path ) {
		if ( subSchema.type === 'object' ) {
			subSchema = subSchema.properties[ p ];
		} else if ( subSchema.type === 'array' && (
			subSchema.control || subSchema.items.type === 'string'
		) ) {
			return ensureTargetIsArrayNotItem( pointer );
		} else if ( subSchema.type === 'array' ) {
			subSchema = subSchema.items;
		}
	}

	return pointer;
}

function removeDeletedArrayItemErrors( pointerToArray, indexOfDeletedItem ) {
	const idOfDeletedItem = pointerToArray + '.' + indexOfDeletedItem;
	state.validationErrors = state.validationErrors.filter(
		( error ) => !error.formFieldId.startsWith( idOfDeletedItem )
	);
}

function decrementArrayItemErrorIndexes( pointerToArray, indexOfDeletedItem ) {
	state.validationErrors = state.validationErrors.map(
		( error ) => {
			if (
				error.formFieldId.startsWith( pointerToArray ) &&
				error.formFieldId.length > pointerToArray.length
			) {
				const parts = error.formFieldId.slice( pointerToArray.length ).split( '.' );
				const index = parseInt( parts[ 1 ], 10 );

				if ( index > indexOfDeletedItem ) {
					parts[ 1 ] = index - 1;
					error.formFieldId = pointerToArray + parts.join( '.' );
				}
			}
			return error;
		}
	);
}

// endregion --private methods
