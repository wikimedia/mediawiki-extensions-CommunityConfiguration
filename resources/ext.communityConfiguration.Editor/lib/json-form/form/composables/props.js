/**
 * @typedef RendererProps
 * @property {Object} schema JSON schema for the data
 * @property {Object} uischema Schema for the form UI
 * @property {Array<Object>} renderers A collection of renderers
 */

/**
 * Common props for all renderers
 *
 * @return {RendererProps} The RendererProps definition for renderers
 */
const rendererProps = () => ( {
	schema: {
		required: true,
		type: [ Object, Boolean ],
	},
	uischema: {
		required: true,
		type: Object,
	},
	renderers: {
		required: false,
		type: Array,
		default: undefined,
	},
} );

module.exports = exports = {
	rendererProps,
};
