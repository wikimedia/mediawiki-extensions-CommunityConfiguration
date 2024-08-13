'use strict';

const { mount } = require( '@vue/test-utils' );
const ClientErrorMessage = require( './ClientErrorMessage.vue' );

describe( 'ClientErrorMessage', () => {
	it( 'matches snapshot', () => {
		const errorObject = new Error( 'test error' );
		errorObject.stack = '[stack trace here]';
		const wrapper = mount( ClientErrorMessage, {
			props: {
				componentName: 'App',
				info: 'setup function',
				err: errorObject,
				feedbackURL: 'https://example.com/feedback'
			},
			global: {
				...global.getGlobalMediaWikiMountingOptions()
			}
		} );

		expect( wrapper.element ).toMatchSnapshot();
	} );
} );
