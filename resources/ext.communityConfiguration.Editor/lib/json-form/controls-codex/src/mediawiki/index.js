const { MEDIAWIKI_DEFINITIONS } = require( './constants.js' );
const PageTitleControl = require( './PageTitleControl.vue' );

const {
	rankWith,
	schemaRefIs
} = require( '../../config/index.js' );

const rankRenderer = ( rank, renderer, tester ) => ( {
	renderer,
	tester: rankWith( rank, tester )
} );

const isPageTitleControl = schemaRefIs( MEDIAWIKI_DEFINITIONS.PAGE_TITLE );

module.exports = exports = {
	mediawiki: [
		// renderers ranked based on schema refs need to have
		// > 1 rank to precede basic type renderers
		rankRenderer( 2, PageTitleControl, isPageTitleControl )
	]
};
