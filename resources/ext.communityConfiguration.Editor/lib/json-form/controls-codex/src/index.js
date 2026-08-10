const { controls } = require( './controls/index.js' );
const { complex } = require( './complex/index.js' );
const { mediawiki } = require( './mediawiki/index.js' );
const { layouts } = require( './layouts/index.js' );

module.exports = exports = {
	renderers: [
		...controls,
		...complex,
		...mediawiki,
		...layouts,
	],
};
