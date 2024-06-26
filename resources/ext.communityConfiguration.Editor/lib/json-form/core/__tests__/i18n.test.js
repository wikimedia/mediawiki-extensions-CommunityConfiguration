'use strict';
const { getControlTextProps } = require( '../i18n.js' );
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
				controlSchema
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
