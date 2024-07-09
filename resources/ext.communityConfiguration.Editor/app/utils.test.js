'use strict';

const { adjustPointerForValidationErrors } = require( './utils.js' );

describe( 'adjustPointerForValidationErrors', () => {
	it( 'removes index from a pointer for an array with a custom control', () => {
		const pointer = '/GEHelpPanelExcludedNamespaces/0';
		const schema = {
			additionalProperties: false,
			type: 'object',

			properties: {
				GEHelpPanelExcludedNamespaces: {
					type: 'array',
					items: { type: 'integer' },
					default: [],
					control: 'MediaWiki\\\\Extension\\\\CommunityConfiguration\\\\Controls\\\\NamespacesControl'
				}
			}
		};
		const expectedReturnedPointer = '/GEHelpPanelExcludedNamespaces';

		const actualReturnedPointer = adjustPointerForValidationErrors( schema, pointer );

		expect( actualReturnedPointer ).toEqual( expectedReturnedPointer );

	} );

	it( 'removes index from a pointer for an array consisting only of strings', () => {
		const pointer = '/AutoModeratorSkipUserGroups/2';
		const schema = { additionalProperties: false, type: 'object', properties: {
			AutoModeratorSkipUserGroups: {
				type: 'array',
				default: [ 'bot', 'sysop' ],
				items: {
					enum: [ 'bot', 'sysop' ],
					type: 'string'
				}
			}
		} };
		const expectedReturnedPointer = '/AutoModeratorSkipUserGroups';

		const actualReturnedPointer = adjustPointerForValidationErrors( schema, pointer );

		expect( actualReturnedPointer ).toEqual( expectedReturnedPointer );
	} );

	it( 'does nothing if the pointer does not include an index', () => {
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
		const expectedReturnedPointer = '/link_recommendation/excludedSections';

		const actualReturnedPointer = adjustPointerForValidationErrors( schema, pointer );

		expect( actualReturnedPointer ).toEqual( expectedReturnedPointer );
	} );

	it( 'does nothing if the pointer is for a normal array', () => {
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
		const expectedReturnedPointer = '/GEHelpPanelLinks/1/text';

		const actualReturnedPointer = adjustPointerForValidationErrors( schema, pointer );

		expect( actualReturnedPointer ).toEqual( expectedReturnedPointer );
	} );
} );
