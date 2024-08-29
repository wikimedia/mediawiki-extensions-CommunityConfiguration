'use strict';

const {
	useJsonFormArrayControl,
	useJsonFormControl,
} = require( './useJsonForm.js' );
const { createApp } = require( 'vue' );

/* eslint-disable jsdoc/valid-types */
/**
 * withSetup<T>
 *
 * @param {() => T} composable
 * @param {Object} provides
 * @return {[T,*]}
 */
function withSetup( composable, provides = {} ) {
	let result;
	const app = createApp( {
		setup() {
			result = composable();
			return () => {
			};
		},
	} );

	Object.entries( provides ).forEach( ( [ key, value ] ) => {
		app.provide( key, value );
	} );
	app.mount( document.createElement( 'div' ) );
	return [ result, app ];
}
/* eslint-enable jsdoc/valid-types */

describe( 'useJsonFormControl', () => {

	describe( 'otherAttributes', () => {
		it( 'sets `required`', () => {
			const props = {
				uischema: { required: true, scope: '#/properties/number' },
				schema: {},
				renderers: [],
			};
			const jsonform = {
				schema: {},
				config: {
					i18nPrefix: 'i18n-prefix',
				},
			};
			const [ result ] = withSetup(
				() => useJsonFormControl( props ),
				{ jsonform, i18n: global.mw.Message },
			);
			expect( result.control.otherAttrs.required ).toBe( true );
		} );
	} );

	describe( 'pointer handling', () => {
		it( 'sets pointer for toplevel control', () => {
			const props = {
				uischema: { scope: '#/properties/numberName' },
				schema: {},
				renderers: [],
			};
			const jsonform = {
				schema: {},
				config: {
					i18nPrefix: 'i18n-prefix',
				},
			};
			const [ result ] = withSetup(
				() => useJsonFormControl( props ),
				{ jsonform, i18n: global.mw.Message },
			);
			expect( result.control.pointer ).toBe( 'numberName' );
		} );

		it( 'constructs pointer for nested control', () => {
			const props = {
				uischema: { scope: '#/properties/ArrayName/0/properties/NumberName' },
				schema: {},
				renderers: [],
			};
			const jsonform = {
				schema: {},
				config: {
					i18nPrefix: 'i18n-prefix',
				},
			};
			const [ result ] = withSetup(
				() => useJsonFormControl( props ),
				{ jsonform, i18n: global.mw.Message },
			);
			expect( result.control.pointer ).toBe( 'ArrayName.0.NumberName' );
		} );
	} );
} );

describe( 'useJsonFormArrayControl', () => {
	beforeAll( () => {
		global.mw.messages = [];
		global.mw.Message = jest.fn( ( messages, key ) => ( {
			exists: jest.fn( () => messages.includes( key ) ),
		} ) );
	} );

	describe( 'otherAttributes', () => {
		it( 'sets `required`', () => {
			const props = {
				uischema: { required: true, scope: '#/properties/number' },
				schema: {
					type: 'array',
				},
				renderers: [],
			};
			const jsonform = {
				schema: {},
				config: {
					i18nPrefix: 'i18n-prefix',
				},
			};
			const [ result ] = withSetup(
				() => useJsonFormArrayControl( props ),
				{ jsonform, i18n: global.mw.Message },
			);
			expect( result.control.otherAttrs.required ).toBe( true );
		} );
	} );

	describe( 'pointer handling', () => {
		it( 'sets pointer', () => {
			const props = {
				uischema: { scope: '#/properties/arrayName' },
				schema: {
					type: 'array',
				},
				renderers: [],
			};
			const jsonform = {
				schema: {},
				config: {
					i18nPrefix: 'i18n-prefix',
				},
			};
			const [ result ] = withSetup(
				() => useJsonFormArrayControl( props ),
				{ jsonform, i18n: global.mw.Message },
			);
			expect( result.control.pointer ).toBe( 'arrayName' );
		} );
	} );
} );
