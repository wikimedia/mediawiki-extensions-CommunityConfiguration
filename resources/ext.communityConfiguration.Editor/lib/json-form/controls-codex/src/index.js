const { controls } = require( './controls/index.js' );
const { complex } = require( './complex/index.js' );

module.exports = exports = {
	renderers: [
		...controls,
		...complex
	]
};
