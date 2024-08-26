'use strict';
const { getControlTextProps, getLabelsChain } = require( '../i18n.js' );
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
		expected: [
			'testenvironment-someprovider-exampleobject-label',
			'testenvironment-someprovider-exampleobject-help-text',
			'testenvironment-someprovider-exampleobject-description'
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
							}
						}
					}
				}
			}
		},
		expected: [
			'testenvironment-someprovider-examplearray-label',
			'testenvironment-someprovider-examplearray-help-text',
			'testenvironment-someprovider-examplearray-description',
			'testenvironment-someprovider-examplearray-item-label',
			'testenvironment-someprovider-examplearray-add-element-button-label'
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
		expected: [
			'testenvironment-someprovider-basicstringinput-label',
			'testenvironment-someprovider-basicstringinput-help-text',
			'testenvironment-someprovider-basicstringinput-placeholder',
			'testenvironment-someprovider-basicstringinput-description'
		]
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
		expected: [
			'testenvironment-someprovider-basicnumberinput-label',
			'testenvironment-someprovider-basicnumberinput-help-text',
			'testenvironment-someprovider-basicnumberinput-placeholder',
			'testenvironment-someprovider-basicnumberinput-description'
		]
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
		expected: [
			'testenvironment-someprovider-basicintegerinput-label',
			'testenvironment-someprovider-basicintegerinput-help-text',
			'testenvironment-someprovider-basicintegerinput-placeholder',
			'testenvironment-someprovider-basicintegerinput-description'
		]
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
		expected: [
			'testenvironment-someprovider-basicboolean-label',
			'testenvironment-someprovider-basicboolean-control-label',
			'testenvironment-someprovider-basicboolean-help-text',
			'testenvironment-someprovider-basicboolean-description'
		]
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
		expected: [
			'testenvironment-someprovider-stringenum-label',
			'testenvironment-someprovider-stringenum-help-text',
			'testenvironment-someprovider-stringenum-description',
			'testenvironment-someprovider-stringenum-option-fire-label',
			'testenvironment-someprovider-stringenum-option-water-label',
			'testenvironment-someprovider-stringenum-option-earth-label',
			'testenvironment-someprovider-stringenum-option-air-label'
		]
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
		expected: [
			'testenvironment-someprovider-numberenum-label',
			'testenvironment-someprovider-numberenum-help-text',
			'testenvironment-someprovider-numberenum-description',
			'testenvironment-someprovider-numberenum-option-1-label',
			'testenvironment-someprovider-numberenum-option-7-label',
			'testenvironment-someprovider-numberenum-option-9-label',
			'testenvironment-someprovider-numberenum-option-13-label'
		]
	},
	{
		testType: 'array-of-enum-number',
		schema: {
			type: 'object',
			properties: {
				numberEnumArray: {
					type: 'array',
					items: {
						type: 'number',
						enum: [ 1, 7, 9, 13 ]
					}
				}
			}
		},
		expected: [
			'testenvironment-someprovider-numberenumarray-label',
			'testenvironment-someprovider-numberenumarray-help-text',
			'testenvironment-someprovider-numberenumarray-description',
			'testenvironment-someprovider-numberenumarray-option-1-label',
			'testenvironment-someprovider-numberenumarray-option-7-label',
			'testenvironment-someprovider-numberenumarray-option-9-label',
			'testenvironment-someprovider-numberenumarray-option-13-label'
		]
	},
	{
		testType: 'array-of-enum-string',
		schema: {
			type: 'object',
			properties: {
				stringEnumArray: {
					type: 'array',
					items: {
						type: 'string',
						enum: [ 'fire', 'water', 'earth', 'air' ]
					}
				}
			}
		},
		expected: [
			'testenvironment-someprovider-stringenumarray-label',
			'testenvironment-someprovider-stringenumarray-help-text',
			'testenvironment-someprovider-stringenumarray-description',
			'testenvironment-someprovider-stringenumarray-option-fire-label',
			'testenvironment-someprovider-stringenumarray-option-water-label',
			'testenvironment-someprovider-stringenumarray-option-earth-label',
			'testenvironment-someprovider-stringenumarray-option-air-label'
		]
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
		expected: [
			'testenvironment-someprovider-pagetitle-label',
			'testenvironment-someprovider-pagetitle-help-text',
			'testenvironment-someprovider-pagetitle-placeholder',
			'testenvironment-someprovider-pagetitle-description'
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
						type: 'string'
					}
				}
			}
		},
		expected: [
			'testenvironment-someprovider-pagetitles-label',
			'testenvironment-someprovider-pagetitles-help-text',
			'testenvironment-someprovider-pagetitles-description'
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
		expected: [
			'testenvironment-someprovider-namespaces-label',
			'testenvironment-someprovider-namespaces-help-text',
			'testenvironment-someprovider-namespaces-description'
		]
	}
];

global.mw = {
	Message: class {
		constructor( messages, key ) {
			this.key = key;
			this.parameters = [];
		}

		exists() {
			return true;
		}

		getKey() {
			return this.key;
		}

		getCompositeKey() {
			if ( this.parameters.length === 0 ) {
				return this.key;
			}
			const paramString = this.parameters.join( ', ' );
			return this.key + ': ' + paramString;
		}

		params( parameters ) {
			this.parameters.push( ...parameters );
			return this;
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
				controlSchema
			);

			expect(
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
					keys.push( ...Object.values( messageFake ) );
					return keys;
				}, [] )
			).toEqual( testData.expected );
		} );
	}
} );

describe( 'i18n.getLabelsChain', () => {
	it( 'returns nested labels for field in object', () => {
		const pointer = '/link_recommendation/excludedSections';
		const schema = {
			additionalProperties: false,
			type: 'object',
			properties: {
				// eslint-disable-next-line camelcase
				link_recommendation: {
					type: 'object',
					properties: {
						excludedSections: {
							type: 'array',
							items: {
								type: 'string'
							},
							default: []
						}
					},
					default: null
				}
			}
		};
		const actualLabels = getLabelsChain( schema, pointer, 'testenvironment-someprovider' );
		expect(
			actualLabels.map( ( msg ) => msg.getKey() )
		).toEqual( [
			'testenvironment-someprovider-link_recommendation-label',
			'testenvironment-someprovider-link_recommendation-excludedsections-label'
		] );
	} );

	it( 'returns nested labels for item in array', () => {
		const pointer = '/GEHelpPanelLinks/1/text';
		const schema = {
			additionalProperties: false,
			type: 'object',
			properties: {
				GEHelpPanelLinks: {
					type: 'array',
					items: {
						type: 'object',
						properties: {
							title: {
								type: 'string',
								default: '',
								control: 'MediaWiki\\Extension\\CommunityConfiguration\\Controls\\PageTitleControl'
							},
							text: {
								type: 'string'
							}
						}
					},
					default: [],
					maxItems: 10
				}
			}
		};

		const actualLabels = getLabelsChain( schema, pointer, 'testenvironment-someprovider' );

		expect(
			actualLabels.map( ( msg ) => msg.getCompositeKey() )
		).toEqual( [
			'testenvironment-someprovider-gehelppanellinks-label',
			'testenvironment-someprovider-gehelppanellinks-item-label: 2',
			'testenvironment-someprovider-gehelppanellinks-text-label'
		] );
	} );
} );
