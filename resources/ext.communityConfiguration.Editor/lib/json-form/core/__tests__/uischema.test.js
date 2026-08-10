'use strict';
const { buildUISchema } = require( '../uischema.js' );
const testJsonSchema = require( './test-json-schema.json' );
const editorConfig = {
	i18nPrefix: 'testenvironment-someprovider',
};
const testJsonConfig = {
	ExampleString: 'Some string',
	ExampleArray: [
		'Some string',
		'Some other string',
	],
};

function findUISchemaElement( needle, haystack ) {
	return haystack.find( ( el ) => el.name === needle );
}

function assertUISchemaElementDefaults( subschemaName, uischema ) {
	const uiSchemaElement = findUISchemaElement( subschemaName, uischema.elements );
	// Maybe throw if no uiSchemaElement is found
	expect( uiSchemaElement.type ).toEqual( 'Control' );
	expect( uiSchemaElement.scope ).toBeDefined();
	expect( uiSchemaElement.label ).toBeDefined();
	expect( uiSchemaElement.label.text() ).toBe(
		`testenvironment-someprovider-${ subschemaName.toLowerCase() }-label`,
	);
	// TODO assert control label and help text
}

function assertUISchemaArrayDefaults( subschemaName, uischema ) {
	const uiSchemaElement = findUISchemaElement( subschemaName, uischema.elements );
	expect( uiSchemaElement.itemLabel ).toBeDefined();
	expect( uiSchemaElement.itemLabel.text() ).toBe(
		`testenvironment-someprovider-${ subschemaName.toLowerCase() }-item-label`,
	);
}

describe( 'UISchema', () => {
	beforeAll( () => {
		global.mw.messages = [
			'testenvironment-someprovider-examplestring-label',
			'testenvironment-someprovider-examplearray-label',
			'testenvironment-someprovider-examplearray-item-label',
			'testenvironment-someprovider-exampleobject-label',
		];
		global.mw.Message = jest.fn( ( messages, key ) => ( {
			exists: jest.fn( () => messages.includes( key ) ),
			text: jest.fn( () => key ),
			parse: jest.fn( () => key ),
		} ) );
	} );

	it( 'should produce a UI schema given a Json schema', () => {
		const actual = buildUISchema( testJsonSchema, editorConfig, '', testJsonConfig );
		// Assert an element is produced for each top schema property
		expect( actual.elements.length ).toEqual(
			Object.keys( testJsonSchema.properties ).length,
		);
		// Assert each top schema property has proper defaults
		for ( const prop in testJsonSchema.properties ) {
			assertUISchemaElementDefaults( prop, actual );
			const propType = testJsonSchema.properties[ prop ].type;
			switch ( propType ) {
				case 'array':
					assertUISchemaArrayDefaults( prop, actual, testJsonConfig );
					break;
				default:
					break;
			}
		}
	} );

	describe( 'extra property handling', () => {
		it( 'drops extra properties from the data object main level', () => {
			const testJsonConfigWithExtraData = {
				ExampleString: 'Some string',
				ExampleArray: [
					'Some string',
					'Some other string',
				],
				Extra: 'should be dropped',
				ExampleObject: {
					ExampleBoolean: true,
					NestedExtra: 'remains untouched in this iteration',
				},
			};
			const expectedData = {
				ExampleString: 'Some string',
				ExampleArray: [
					'Some string',
					'Some other string',
				],
				ExampleObject: {
					ExampleBoolean: true,
					NestedExtra: 'remains untouched in this iteration',
				},
			};

			buildUISchema( testJsonSchema, editorConfig, '', testJsonConfigWithExtraData );

			expect( testJsonConfigWithExtraData ).toEqual( expectedData );
		} );

		it( 'keeps extra properties when they are explicitly allowed', () => {
			const testSchema = {
				type: 'object',
				default: null,
				properties: {
					ExampleBoolean: {
						type: 'boolean',
						default: false,
					},
					ExampleNumber: {
						type: 'number',
						default: null,
					},
				},
				additionalProperties: true,
			};
			const testData = {
				ExampleBoolean: true,
				NestedExtra: 'remains untouched because it is allowed',
			};
			const expectedData = { ...testData };

			buildUISchema( testSchema, editorConfig, '', testData );

			expect( testData ).toEqual( expectedData );
		} );

		it( 'keeps extra properties when `additionalProperties` is not set', () => {
			const testSchema = {
				type: 'object',
				default: null,
				properties: {
					ExampleBoolean: {
						type: 'boolean',
						default: false,
					},
					ExampleNumber: {
						type: 'number',
						default: null,
					},
				},
			};
			const testData = {
				ExampleBoolean: true,
				NestedExtra: 'remains untouched because it is implicitly allowed',
			};
			const expectedData = { ...testData };

			buildUISchema( testSchema, editorConfig, '', testData );

			expect( testData ).toEqual( expectedData );
		} );
	} );

	describe( 'layout', () => {
		const names = ( elements ) => elements.map( ( el ) => el.name );

		const build = ( uiSchema ) => buildUISchema(
			testJsonSchema, editorConfig, '', { ...testJsonConfig }, uiSchema,
		);

		beforeEach( () => {
			global.mw.log.warn.mockClear();
		} );

		it( 'follows the order of the layout', () => {
			const actual = build( { elements: [
				'#/properties/ExampleArray',
				'#/properties/ExampleString',
				'#/properties/ExampleObject',
			] } );

			expect( names( actual.elements ) )
				.toEqual( [ 'ExampleArray', 'ExampleString', 'ExampleObject' ] );
		} );

		it( 'adds a property the layout does not place, at the end', () => {
			const actual = build( { elements: [ '#/properties/ExampleObject' ] } );

			// No configurable value may disappear from the form, or the editor would save a
			// configuration without it.
			expect( names( actual.elements ) )
				.toEqual( [ 'ExampleObject', 'ExampleString', 'ExampleArray' ] );
		} );

		it( 'puts the children of a Group inside it', () => {
			const actual = build( { elements: [ {
				type: 'Group',
				label: 'exampleobject',
				elements: [ '#/properties/ExampleString', '#/properties/ExampleArray' ],
			}, '#/properties/ExampleObject' ] } );

			expect( actual.elements ).toHaveLength( 2 );
			expect( actual.elements[ 0 ].type ).toBe( 'Group' );
			expect( names( actual.elements[ 0 ].elements ) )
				.toEqual( [ 'ExampleString', 'ExampleArray' ] );
			expect( actual.elements[ 1 ].name ).toBe( 'ExampleObject' );
		} );

		it( 'resolves the heading messages of a Group', () => {
			global.mw.messages.push( 'testenvironment-someprovider-eligibility-section-label' );
			const actual = build( { elements: [ {
				type: 'Group',
				label: 'Eligibility',
				elements: [ '#/properties/ExampleString' ],
			} ] } );

			expect( actual.elements[ 0 ].label.exists() ).toBe( true );
			expect( actual.elements[ 0 ].label.text() )
				.toBe( 'testenvironment-someprovider-eligibility-section-label' );
			// A group commonly has a heading with no description. A message that does not exist
			// becomes null, which is why GroupLayout guards on the message before it reads it.
			expect( actual.elements[ 0 ].description ).toBeNull();
		} );

		it( 'gives a Group without a label no heading', () => {
			const actual = build( { elements: [ {
				type: 'Group',
				elements: [ '#/properties/ExampleString' ],
			} ] } );

			expect( actual.elements[ 0 ].label ).toBeNull();
			expect( actual.elements[ 0 ].description ).toBeNull();
		} );

		it( 'supports a Group inside a Group', () => {
			const actual = build( { elements: [ {
				type: 'Group',
				label: 'outer',
				elements: [ {
					type: 'Group',
					label: 'inner',
					elements: [ '#/properties/ExampleString' ],
				} ],
			} ] } );

			const inner = actual.elements[ 0 ].elements[ 0 ];
			expect( inner.type ).toBe( 'Group' );
			expect( names( inner.elements ) ).toEqual( [ 'ExampleString' ] );
		} );

		it( 'copies the control and the options of a Control element', () => {
			const actual = build( { elements: [ {
				type: 'Control',
				scope: '#/properties/ExampleArray',
				control: 'Test.Lookup',
				options: { outputType: 'Z89' },
			} ] } );

			const element = findUISchemaElement( 'ExampleArray', actual.elements );
			expect( element.control ).toBe( 'Test.Lookup' );
			expect( element.options ).toEqual( { outputType: 'Z89' } );
		} );

		it( 'gives a Control element without options an empty options object', () => {
			const actual = build( { elements: [ {
				type: 'Control',
				scope: '#/properties/ExampleArray',
				control: 'Test.Lookup',
			} ] } );

			expect( findUISchemaElement( 'ExampleArray', actual.elements ).options ).toEqual( {} );
		} );

		it( 'leaves control unset when the layout asks for no control', () => {
			const actual = build( { elements: [ '#/properties/ExampleArray' ] } );

			expect( findUISchemaElement( 'ExampleArray', actual.elements ).control )
				.toBeUndefined();
		} );

		it( 'skips an unknown element type without consuming its scope', () => {
			const actual = build( { elements: [
				{ type: 'HorizontalLayout', elements: [ '#/properties/ExampleString' ] },
				'#/properties/ExampleArray',
			] } );

			// The element is skipped, but ExampleString is not marked as placed, so the safety
			// net still adds it. Otherwise the field would vanish and the editor would save a
			// configuration without it.
			expect( names( actual.elements ) )
				.toEqual( [ 'ExampleArray', 'ExampleString', 'ExampleObject' ] );
			expect( global.mw.log.warn ).toHaveBeenCalled();
		} );

		it( 'rejects a scope below the top level', () => {
			const actual = build( { elements: [
				'#/properties/ExampleObject/properties/ExampleBoolean',
			] } );

			// The scope must not silently resolve to ExampleObject.
			expect( names( actual.elements ) )
				.toEqual( [ 'ExampleString', 'ExampleArray', 'ExampleObject' ] );
			expect( global.mw.log.warn ).toHaveBeenCalled();
		} );

		it( 'rejects a scope that the data schema does not have', () => {
			const actual = build( { elements: [ '#/properties/NoSuchProperty' ] } );

			expect( names( actual.elements ) )
				.toEqual( [ 'ExampleString', 'ExampleArray', 'ExampleObject' ] );
			expect( global.mw.log.warn ).toHaveBeenCalled();
		} );

		it( 'places a property once, even when the layout names it twice', () => {
			const actual = build( { elements: [
				'#/properties/ExampleString',
				'#/properties/ExampleString',
			] } );

			// Two controls for one property would both write the same value.
			expect( names( actual.elements ) )
				.toEqual( [ 'ExampleString', 'ExampleArray', 'ExampleObject' ] );
			expect( global.mw.log.warn ).toHaveBeenCalled();
		} );

		it( 'ignores an empty layout', () => {
			const actual = build( { elements: [] } );

			expect( names( actual.elements ) )
				.toEqual( [ 'ExampleString', 'ExampleArray', 'ExampleObject' ] );
		} );

		it( 'ignores the layout for a nested object', () => {
			// ObjectControl calls buildUISchema again with the scope of the parent. A layout of
			// the form cannot describe the subschema of a nested object, so every scope of it
			// would miss and the groups would come out empty.
			const actual = buildUISchema(
				testJsonSchema.properties.ExampleObject,
				editorConfig,
				'#/properties/ExampleObject',
				{},
				{ elements: [ { type: 'Group', label: 'g', elements: [ '#/properties/Nope' ] } ] },
			);

			expect( names( actual.elements ) ).toEqual( [ 'ExampleBoolean', 'ExampleNumber' ] );
		} );

		it( 'still drops data properties the schema does not have', () => {
			const data = { ExampleString: 'a', Extra: 'should be dropped' };

			buildUISchema( testJsonSchema, editorConfig, '', data, {
				elements: [ '#/properties/ExampleString' ],
			} );

			expect( data ).toEqual( { ExampleString: 'a' } );
		} );
	} );
} );
