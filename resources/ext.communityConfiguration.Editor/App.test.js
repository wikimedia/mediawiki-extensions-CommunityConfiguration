const { mount } = require( '@vue/test-utils' );
const App = require( './App.vue' );

describe( 'ext.communityConfiguration.Editor App', () => {
	it( 'displays the app', () => {
		const wrapper = mount( App );
		expect( wrapper.text() ).toContain( 'Hello world Vue' );
	} );
} );
