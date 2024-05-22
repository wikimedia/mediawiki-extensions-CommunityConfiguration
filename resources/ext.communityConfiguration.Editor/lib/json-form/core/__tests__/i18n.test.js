'use strict';
const { getEditorTextKeys, getControlsTextKeys, getControlTextProps } = require( '../i18n.js' );
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
			'testenvironment-someprovider-exampleobject-help-text'
		],
		expectedSubControlKeys: [
			'testenvironment-someprovider-exampleobject-foo-label',
			'testenvironment-someprovider-exampleobject-foo-help-text',
			'testenvironment-someprovider-exampleobject-foo-placeholder',
			'testenvironment-someprovider-exampleobject-bar-label',
			'testenvironment-someprovider-exampleobject-bar-help-text',
			'testenvironment-someprovider-exampleobject-bar-placeholder'
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
			'testenvironment-someprovider-examplearray-help-text',
			'testenvironment-someprovider-examplearray-0-label',
			'testenvironment-someprovider-examplearray-1-label'
		],
		expectedSubControlKeys: [
			'testenvironment-someprovider-examplearray-foo-label',
			'testenvironment-someprovider-examplearray-foo-help-text',
			'testenvironment-someprovider-examplearray-foo-placeholder',
			'testenvironment-someprovider-examplearray-bar-label',
			'testenvironment-someprovider-examplearray-bar-help-text',
			'testenvironment-someprovider-examplearray-bar-placeholder'
		]
	},
	{
		testType: 'string',
		schema: {
			type: 'object',
			properties: {
				basicStringInput: {
					type: 'string'
				}
			}
		},
		config: {},
		expected: [
			'testenvironment-someprovider-basicstringinput-label',
			'testenvironment-someprovider-basicstringinput-help-text',
			'testenvironment-someprovider-basicstringinput-placeholder'
		],
		expectedSubControlKeys: []
	},
	{
		testType: 'number',
		schema: {
			type: 'object',
			properties: {
				basicNumberInput: {
					type: 'number'
				}
			}
		},
		config: {},
		expected: [
			'testenvironment-someprovider-basicnumberinput-label',
			'testenvironment-someprovider-basicnumberinput-help-text',
			'testenvironment-someprovider-basicnumberinput-placeholder'
		],
		expectedSubControlKeys: []
	},
	{
		testType: 'integer',
		schema: {
			type: 'object',
			properties: {
				basicIntegerInput: {
					type: 'integer'
				}
			}
		},
		config: {},
		expected: [
			'testenvironment-someprovider-basicintegerinput-label',
			'testenvironment-someprovider-basicintegerinput-help-text',
			'testenvironment-someprovider-basicintegerinput-placeholder'
		],
		expectedSubControlKeys: []
	},
	{
		testType: 'boolean',
		schema: {
			type: 'object',
			properties: {
				basicBoolean: {
					type: 'boolean'
				}
			}
		},
		config: {},
		expected: [
			'testenvironment-someprovider-basicboolean-label',
			'testenvironment-someprovider-basicboolean-control-label',
			'testenvironment-someprovider-basicboolean-help-text'
		],
		expectedSubControlKeys: []
	},
	{
		testType: 'enum-string',
		schema: {
			type: 'object',
			properties: {
				stringEnum: {
					type: 'string',
					enum: [ 'fire', 'water', 'earth', 'air' ]
				}
			}
		},
		config: {},
		expected: [
			'testenvironment-someprovider-stringenum-label',
			'testenvironment-someprovider-stringenum-help-text',
			'testenvironment-someprovider-stringenum-option-fire-label',
			'testenvironment-someprovider-stringenum-option-water-label',
			'testenvironment-someprovider-stringenum-option-earth-label',
			'testenvironment-someprovider-stringenum-option-air-label'
		],
		expectedSubControlKeys: []
	},
	{
		testType: 'enum-number',
		schema: {
			type: 'object',
			properties: {
				numberEnum: {
					type: 'number',
					enum: [ 1, 7, 9, 13 ]
				}
			}
		},
		config: {},
		expected: [
			'testenvironment-someprovider-numberenum-label',
			'testenvironment-someprovider-numberenum-help-text',
			'testenvironment-someprovider-numberenum-option-1-label',
			'testenvironment-someprovider-numberenum-option-7-label',
			'testenvironment-someprovider-numberenum-option-9-label',
			'testenvironment-someprovider-numberenum-option-13-label'
		],
		expectedSubControlKeys: []
	},
	{
		testType: 'pagetitle',
		schema: {
			type: 'object',
			properties: {
				pageTitle: {
					type: 'string',
					control: 'MediaWiki\\Extension\\CommunityConfiguration\\Controls\\PageTitleControl',
					default: ''
				}
			}
		},
		config: {},
		expected: [
			'testenvironment-someprovider-pagetitle-label',
			'testenvironment-someprovider-pagetitle-help-text',
			'testenvironment-someprovider-pagetitle-placeholder'
		],
		expectedSubControlKeys: [
			'communityconfiguration-page-title-control-no-results'
		]
	},
	{
		testType: 'pagetitles',
		schema: {
			type: 'object',
			properties: {
				pageTitles: {
					type: 'array',
					control: 'MediaWiki\\Extension\\CommunityConfiguration\\Controls\\PageTitlesControl',
					default: [],
					items: {
						control: 'MediaWiki\\Extension\\CommunityConfiguration\\Controls\\PageTitlesControl',
						type: 'string'
					}
				}
			}
		},
		config: {
			pageTitles: []
		},
		expected: [
			'testenvironment-someprovider-pagetitles-label',
			'testenvironment-someprovider-pagetitles-help-text'
		],
		expectedSubControlKeys: [
			'mw-widgets-titlesmultiselect-placeholder',
			'communityconfiguration-editor-chip-control-aria-chip-description'
		]
	},
	{
		testType: 'namespaces',
		schema: {
			type: 'object',
			properties: {
				namespaces: {
					type: 'array',
					control: 'MediaWiki\\Extension\\CommunityConfiguration\\Controls\\NamespacesControl',
					default: [],
					items: {
						type: 'integer'
					}
				}
			}
		},
		config: {
			namespaces: []
		},
		expected: [
			'testenvironment-someprovider-namespaces-label',
			'testenvironment-someprovider-namespaces-help-text'
		],
		expectedSubControlKeys: [
			'mw-widgets-titlesmultiselect-placeholder',
			'communityconfiguration-editor-chip-control-aria-chip-description'
		]
	}
];

describe( 'i18n.getControlsTextKeys()', () => {
	for ( const testData of TEST_DATA ) {
		it( `should produce all necessary form labels for the given schema (type: ${ testData.testType })`, () => {
			const actual = getControlsTextKeys( testData.schema, testData.config, {
				i18nTextKeyPrefix: 'testenvironment-someprovider'
			} );
			expect( actual ).toEqual( [
				...testData.expected,
				...testData.expectedSubControlKeys
			] );
		} );
	}
} );

describe( 'i18n.getEditorTextKeys()', () => {
	const [ rootSchema, config, expectedSchemaMessages ] = TEST_DATA.reduce( ( acc, curr ) => {
		Object.assign( acc[ 0 ].properties, curr.schema.properties );
		Object.assign( acc[ 1 ], curr.config );
		acc[ 2 ] = [ ...acc[ 2 ], ...curr.expected, ...curr.expectedSubControlKeys ];
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
		...new Set( expectedSchemaMessages )
	];
	it( 'should produce all necessary form labels for the given schema', () => {
		const actual = getEditorTextKeys( rootSchema, config, {
			i18nTextKeyPrefix: 'testenvironment-someprovider'
		} );
		expect( actual ).toEqual( expectedMessages );
	} );
} );

global.mw = {
	Message: class {
		constructor( messages, key ) {
			this.key = key;
		}

		exists() {
			return true;
		}

		getKey() {
			return this.key;
		}
	}
};

describe( 'i18n.getControlTextProps()', () => {
	for ( const testData of TEST_DATA ) {
		it( `should return an object holding mw.Message objects for all keys needed for a control (type: ${ testData.testType })`, () => {
			const propName = Object.keys( testData.schema.properties )[ 0 ];
			const controlSchema = testData.schema.properties[ propName ];

			const actualData = getControlTextProps(
				propName,
				'testenvironment-someprovider',
				controlSchema,
				testData.config[ propName ] || {}
			);

			expect(
				/* eslint-disable-next-line es-x/no-object-values */
				Object.values( actualData ).reduce( ( keys, messageFake ) => {
					if ( messageFake instanceof global.mw.Message ) {
						keys.push( messageFake.getKey() );
						return keys;
					}
					if ( Array.isArray( messageFake ) ) {
						keys.push( ...messageFake.map( ( msg ) => msg.getKey() ) );
						return keys;
					}

					// enum object now, contains only literal message keys
					/* eslint-disable-next-line es-x/no-object-values */
					keys.push( ...Object.values( messageFake ) );
					return keys;
				}, [] )
			).toEqual( testData.expected );
		} );
	}
} );
