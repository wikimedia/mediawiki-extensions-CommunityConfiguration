'use strict';
const { getEditorTextKeys, getControlsTextKeys } = require( '../i18n.js' );
const TEST_DATA = [
	{
		testType: 'object',
		schema: {
			type: 'object',
			properties: {
				exampleObject: {
					type: 'object',
					properties: {
						foo: {
							type: 'string'
						},
						bar: {
							type: 'number'
						}
					}
				}
			}
		},
		config: {},
		expected: [
			'testenvironment-someprovider-exampleobject-label',
			'testenvironment-someprovider-exampleobject-control-label',
			'testenvironment-someprovider-exampleobject-help-text',
			'testenvironment-someprovider-exampleobject-foo-label',
			'testenvironment-someprovider-exampleobject-foo-control-label',
			'testenvironment-someprovider-exampleobject-foo-help-text',
			'testenvironment-someprovider-exampleobject-bar-label',
			'testenvironment-someprovider-exampleobject-bar-control-label',
			'testenvironment-someprovider-exampleobject-bar-help-text'
		]
	},
	{
		testType: 'array',
		schema: {
			type: 'object',
			properties: {
				exampleArray: {
					type: 'array',
					items: {
						type: 'object',
						properties: {
							foo: {
								type: 'string'
							},
							bar: {
								type: 'number'
							}
						}
					}
				}
			}
		},
		config: {
			exampleArray: [
				{ foo: 'Some value', bar: 1234 },
				{ foo: 'Some value', bar: 1234 }
			]
		},
		expected: [
			'testenvironment-someprovider-examplearray-label',
			'testenvironment-someprovider-examplearray-control-label',
			'testenvironment-someprovider-examplearray-help-text',
			'testenvironment-someprovider-examplearray-0-label',
			'testenvironment-someprovider-examplearray-1-label',
			'testenvironment-someprovider-examplearray-foo-label',
			'testenvironment-someprovider-examplearray-foo-control-label',
			'testenvironment-someprovider-examplearray-foo-help-text',
			'testenvironment-someprovider-examplearray-bar-label',
			'testenvironment-someprovider-examplearray-bar-control-label',
			'testenvironment-someprovider-examplearray-bar-help-text'
		]
	}
];

describe( 'i18n.getControlsTextKeys()', () => {
	for ( const testData of TEST_DATA ) {
		it( `should produce all necessary form labels for the given schema (type: ${testData.testType})`, () => {
			const actual = getControlsTextKeys( testData.schema, testData.config, {
				i18nTextKeyPrefix: 'testenvironment-someprovider'
			} );
			expect( actual ).toEqual( testData.expected );
		} );
	}
} );

describe( 'i18n.getEditorTextKeys()', () => {
	const [ rootSchema, config, expectedSchemaMessages ] = TEST_DATA.reduce( ( acc, curr ) => {
		Object.assign( acc[ 0 ].properties, curr.schema.properties );
		Object.assign( acc[ 1 ], curr.config );
		acc[ 2 ] = [ ...acc[ 2 ], ...curr.expected ];
		return acc;
	}, [
		{
			$id: 'root',
			type: 'object',
			properties: {}
		},
		{},
		[]
	] );

	const expectedMessages = [
		'testenvironment-someprovider-title',
		'testenvironment-someprovider-description',
		...expectedSchemaMessages
	];
	it( 'should produce all necessary form labels for the given schema', () => {
		const actual = getEditorTextKeys( rootSchema, config, {
			i18nTextKeyPrefix: 'testenvironment-someprovider'
		} );
		expect( actual ).toEqual( expectedMessages );
	} );
} );
