const { MEDIAWIKI_DEFINITIONS } = require( './constants.js' );
const PageTitleControl = require( './PageTitleControl.vue' );
const PageTitlesControl = require( './PageTitlesControl.vue' );
const NamespacesControl = require( './NamespacesControl.vue' );

const {
	rankWith,
	schemaControlIs
} = require( '../../config/index.js' );

const rankRenderer = ( rank, renderer, tester ) => ( {
	renderer,
	tester: rankWith( rank, tester )
} );

const isPageTitleControl = schemaControlIs( MEDIAWIKI_DEFINITIONS.PAGE_TITLE );
const isPageTitlesControl = schemaControlIs( MEDIAWIKI_DEFINITIONS.PAGE_TITLES );
const isNamespacesControl = schemaControlIs( MEDIAWIKI_DEFINITIONS.NAMESPACES );

module.exports = exports = {
	mediawiki: [
		// renderers ranked based on schema refs need to have
		// > 1 rank to precede basic type renderers
		rankRenderer( 2, PageTitleControl, isPageTitleControl ),
		rankRenderer( 2, PageTitlesControl, isPageTitlesControl ),
		rankRenderer( 2, NamespacesControl, isNamespacesControl )
	]
};
