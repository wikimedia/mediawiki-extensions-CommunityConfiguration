'use strict';
const { loadCustomRenderers } = require( './customRenderers.js' );

const LookupControl = { name: 'LookupControl' };

describe( 'loadCustomRenderers', () => {
	beforeEach( () => {
		global.mw.loader.using = jest.fn().mockResolvedValue( undefined );
		global.mw.log.warn.mockClear();
	} );

	it( 'returns no renderers when the provider uses no custom control', async () => {
		expect( await loadCustomRenderers( {} ) ).toEqual( [] );
		expect( await loadCustomRenderers( undefined ) ).toEqual( [] );
		// Nothing to wait for, so the form must not wait.
		expect( global.mw.loader.using ).not.toHaveBeenCalled();
	} );

	it( 'loads each module once and makes a renderer of each control', async () => {
		jest.doMock( 'ext.test.lookup', () => ( { LookupControl } ), { virtual: true } );

		const renderers = await loadCustomRenderers( {
			'Test.Lookup': { module: 'ext.test.lookup', component: 'LookupControl' },
			'Test.Other': { module: 'ext.test.lookup', component: 'LookupControl' },
		} );

		expect( global.mw.loader.using ).toHaveBeenCalledWith( [ 'ext.test.lookup' ] );
		expect( renderers ).toHaveLength( 2 );
		expect( renderers[ 0 ].renderer ).toBe( LookupControl );
	} );

	it( 'gives the renderer a tester that matches its own control name only', async () => {
		jest.doMock( 'ext.test.lookup', () => ( { LookupControl } ), { virtual: true } );

		const [ { tester } ] = await loadCustomRenderers( {
			'Test.Lookup': { module: 'ext.test.lookup', component: 'LookupControl' },
		} );

		// Rank 10, so a control the UI schema asks for wins over every built-in control.
		expect( tester( { control: 'Test.Lookup' }, {} ) ).toBe( 10 );
		expect( tester( { control: 'Test.Other' }, {} ) ).toBe( false );
		expect( tester( {}, {} ) ).toBe( false );
	} );

	it( 'skips a control whose module does not export the component', async () => {
		// A module name of its own, because the module registry of Jest keeps the first mock of
		// a name for the whole test file.
		jest.doMock( 'ext.test.noexport', () => ( {} ), { virtual: true } );

		const renderers = await loadCustomRenderers( {
			'Test.Lookup': { module: 'ext.test.noexport', component: 'LookupControl' },
		} );

		expect( renderers ).toEqual( [] );
		expect( global.mw.log.warn ).toHaveBeenCalled();
	} );

	it( 'still mounts the form when a module fails to load', async () => {
		global.mw.loader.using = jest.fn().mockRejectedValue( new Error( 'network' ) );

		const renderers = await loadCustomRenderers( {
			'Test.Lookup': { module: 'ext.test.lookup', component: 'LookupControl' },
		} );

		// No renderer means no tester matches, so the editor falls back to a control of the
		// data schema instead of showing an empty form.
		expect( renderers ).toEqual( [] );
		expect( global.mw.log.warn ).toHaveBeenCalled();
	} );
} );
