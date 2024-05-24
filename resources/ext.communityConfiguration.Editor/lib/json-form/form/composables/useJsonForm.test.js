'use strict';

const {
	useJsonFormArrayControl,
	useJsonFormControl
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
		}
	} );

	// eslint-disable-next-line es-x/no-object-entries
	Object.entries( provides ).forEach( ( [ key, value ] ) => {
		app.provide( key, value );
	} );
	app.mount( document.createElement( 'div' ) );
	return [ result, app ];
}
/* eslint-enable jsdoc/valid-types */

describe( 'useJsonFormControl', () => {

	describe( 'otherAttributes', () => {
		it( 'sets step to `any` for number type', () => {
			const props = {
				uischema: {},
				schema: { type: 'number' },
				renderers: []
			};
			const jsonform = {
				schema: {}
			};
			const [ result ] = withSetup(
				() => useJsonFormControl( props ),
				{ jsonform }
			);
			expect( result.control.otherAttrs.step ).toBe( 'any' );
		} );

		it( 'sets step to `1` for integer type', () => {
			const props = {
				uischema: {},
				schema: { type: 'integer' },
				renderers: []
			};
			const jsonform = {
				schema: {}
			};
			const [ result ] = withSetup(
				() => useJsonFormControl( props ),
				{ jsonform }
			);
			expect( result.control.otherAttrs.step ).toBe( 1 );
		} );

		it( 'sets `required`', () => {
			const props = {
				uischema: { required: true },
				schema: {},
				renderers: []
			};
			const jsonform = {
				schema: {}
			};
			const [ result ] = withSetup(
				() => useJsonFormControl( props ),
				{ jsonform }
			);
			expect( result.control.otherAttrs.required ).toBe( true );
		} );
	} );
} );

describe( 'useJsonFormArrayControl', () => {
	beforeAll( () => {
		global.mw.messages = [];
		global.mw.Message = jest.fn( ( messages, key ) => ( {
			exists: jest.fn( () => messages.includes( key ) )
		} ) );
	} );

	describe( 'otherAttributes', () => {

		it( 'sets `required`', () => {
			const props = {
				uischema: { required: true },
				schema: {
					type: 'array'
				},
				renderers: []
			};
			const jsonform = {
				schema: {},
				config: {
					i18nPrefix: 'i18n-prefix'
				}
			};
			const [ result ] = withSetup(
				() => useJsonFormArrayControl( props ),
				{ jsonform }
			);
			expect( result.control.otherAttrs.required ).toBe( true );
		} );
	} );
} );
