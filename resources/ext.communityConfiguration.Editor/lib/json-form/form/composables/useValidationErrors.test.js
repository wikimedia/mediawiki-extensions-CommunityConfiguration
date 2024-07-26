'use strict';

const { mount } = require( '@vue/test-utils' );
const useValidationErrors = require( './useValidationErrors.js' );

/* eslint-disable jsdoc/no-undefined-types */
/**
 * @param {Object} [rootSchema]
 * @return {CommunityConfiguration_ValidationErrorStore}
 */
function withSetup( rootSchema = {} ) {
	let sut;
	const TestComponent = {
		render() {
		},
		setup() {
			sut = useValidationErrors();
			sut.clearValidationErrors();
		}
	};

	mount( TestComponent, {
		global: {
			provide: {
				jsonform: {
					schema: rootSchema,
					config: { i18nPrefix: 'prefix' }
				}
			}
		}
	} );

	return sut;
}

function withArrayErrors() {
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

	const sut = withSetup( schema );

	sut.setValidationErrorsFromSubmitResponse( { errors: [
		{
			code: 'communityconfiguration-schema-validation-error',
			html: 'DRAFT: The property text is required. Key: GEHelpPanelLinks[2].text',
			data: {
				property: 'GEHelpPanelLinks[2].text',
				pointer: '/GEHelpPanelLinks/2/text',
				messageLiteral: 'The property text is required',
				additionalData: {
					constraint: 'required'
				}
			},
			module: 'communityconfigurationedit'
		},
		{
			code: 'communityconfiguration-schema-validation-error',
			html: 'DRAFT: The property title is required. Key: GEHelpPanelLinks[3].title',
			data: {
				property: 'GEHelpPanelLinks[3].title',
				pointer: '/GEHelpPanelLinks/3/title',
				messageLiteral: 'The property title is required',
				additionalData: {
					constraint: 'required'
				}
			},
			module: 'communityconfigurationedit'
		}
	] } );

	return sut;
}

global.mw = {

	message( key ) {
		// eslint-disable-next-line mediawiki/msg-doc
		return new mw.Message( {}, key );
	},

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

		params( parameters ) {
			this.key = this.key + ': ' + parameters.join( ', ' );
			return this;
		}
	}
};

describe( 'useValidationErrors', () => {
	it( 'defaults to no errors', () => {
		const { getAllValidationErrors } = withSetup();

		const actualErrors = getAllValidationErrors();

		expect( actualErrors ).toStrictEqual( [] );
	} );

	describe( 'setValidationErrorsFromSubmitResponse', () => {

		// FIXME: rephrase these to apply to setValidationErrorsFromSubmitResponse

		const testCases = [
			{
				name: 'does nothing if response is not the usual error format',
				schema: {},
				responseErrors: undefined,
				expectedErrors: []
			},
			{
				name: 'does nothing if response is not validation error',
				schema: {},
				responseErrors: [
					{
						code: 'protectednamespace-interface',
						html: 'This page provides interface text for the software on this wiki, and is protected to prevent abuse.\nTo add or change translations for all wikis, please use <a rel="nofollow" class="external text" href="https://translatewiki.net/">translatewiki.net</a>, the MediaWiki localisation project.',
						module: 'communityconfigurationedit'
					},
					{
						code: 'sitejsonprotected',
						html: 'You do not have permission to edit this JSON page because it may affect all visitors.',
						module: 'communityconfigurationedit'
					}
				],
				expectedErrors: []
			},
			{
				name: 'removes index from a pointer for an array with a custom control',
				schema: {
					additionalProperties: false,
					type: 'object',
					properties: {
						GEHelpPanelExcludedNamespaces: {
							type: 'array',
							items: { type: 'integer' },
							default: [],
							control: 'MediaWiki\\Extension\\CommunityConfiguration\\Controls\\NamespacesControl'
						}
					}
				},
				responseErrors: [
					{
						code: 'communityconfiguration-schema-validation-error',
						html: 'DRAFT: NULL value found, but an integer is required. Key: GEHelpPanelExcludedNamespaces[0]',
						data: {
							property: 'GEHelpPanelExcludedNamespaces[0]',
							pointer: '/GEHelpPanelExcludedNamespaces/0',
							messageLiteral: 'NULL value found, but an integer is required',
							additionalData: {
								constraint: 'type'
							}
						},
						module: 'communityconfigurationedit'
					}
				],
				expectedErrors: [
					{
						additionalData: {
							constraint: 'type'
						},
						formFieldId: 'GEHelpPanelExcludedNamespaces',
						formFieldLabels: [
							mw.message( 'prefix-gehelppanelexcludednamespaces-label' )
						],
						messageLiteral: 'NULL value found, but an integer is required',
						pointer: '/GEHelpPanelExcludedNamespaces/0',
						property: 'GEHelpPanelExcludedNamespaces[0]'
					}
				]
			},
			{
				name: 'removes index from a pointer for an array consisting only of strings',
				schema: {
					additionalProperties: false,
					type: 'object',
					properties: {
						AutoModeratorSkipUserGroups: {
							type: 'array',
							default: [ 'bot', 'sysop' ],
							items: {
								enum: [ 'bot', 'sysop' ],
								type: 'string'
							}
						}
					}
				},
				responseErrors: [
					{
						code: 'communityconfiguration-schema-validation-error',
						html: 'DRAFT: Does not have a value in the enumeration ["bot","sysop"]. Key: AutoModeratorSkipUserGroups[1]',
						data: {
							property: 'AutoModeratorSkipUserGroups[1]',
							pointer: '/AutoModeratorSkipUserGroups/1',
							messageLiteral: 'Does not have a value in the enumeration ["bot","sysop"]',
							additionalData: {
								constraint: 'enum'
							}
						},
						module: 'communityconfigurationedit'
					}
				],
				expectedErrors: [
					{
						additionalData: {
							constraint: 'enum'
						},
						formFieldId: 'AutoModeratorSkipUserGroups',
						formFieldLabels: [
							mw.message( 'prefix-automoderatorskipusergroups-label' )
						],
						messageLiteral: 'Does not have a value in the enumeration ["bot","sysop"]',
						pointer: '/AutoModeratorSkipUserGroups/1',
						property: 'AutoModeratorSkipUserGroups[1]'
					}
				]
			},
			{
				name: 'error for a nested array as a whole',
				schema: {
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
				},
				responseErrors: [
					{
						code: 'communityconfiguration-schema-validation-error',
						html: 'DRAFT: There must be a maximum of 3 items in the array. Key: link_recommendation.excludedSections',
						data: {
							property: 'link_recommendation.excludedSections',
							pointer: '/link_recommendation/excludedSections',
							messageLiteral: 'There must be a maximum of 3 items in the array',
							additionalData: {
								constraint: 'maxItems'
							}
						},
						module: 'communityconfigurationedit'
					}
				],
				expectedErrors: [ {
					additionalData: { constraint: 'maxItems' },
					formFieldId: 'link_recommendation.excludedSections',
					formFieldLabels: [
						mw.message( 'prefix-link_recommendation-label' ),
						mw.message( 'prefix-link_recommendation-excludedsections-label' )
					],
					messageLiteral: 'There must be a maximum of 3 items in the array',
					pointer: '/link_recommendation/excludedSections',
					property: 'link_recommendation.excludedSections'
				} ]
			},
			{
				name: 'error for a nested field in an array',
				schema: {
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
				},
				responseErrors: [
					{
						code: 'communityconfiguration-schema-validation-error',
						html: 'DRAFT: The property title is required. Key: GEHelpPanelLinks[3].title',
						data: {
							property: 'GEHelpPanelLinks[3].title',
							pointer: '/GEHelpPanelLinks/3/title',
							messageLiteral: 'The property title is required',
							additionalData: {
								constraint: 'required'
							}
						},
						module: 'communityconfigurationedit'
					}
				],
				expectedErrors: [
					{
						additionalData: {
							constraint: 'required'
						},
						formFieldId: 'GEHelpPanelLinks.3.title',
						formFieldLabels: [
							mw.message( 'prefix-gehelppanellinks-label' ),
							mw.message( 'prefix-gehelppanellinks-item-label: 4' ),
							mw.message( 'prefix-gehelppanellinks-title-label' )
						],
						messageLiteral: 'The property title is required',
						pointer: '/GEHelpPanelLinks/3/title',
						property: 'GEHelpPanelLinks[3].title'
					}
				]
			}
		];
		it.each( testCases )( '$name', ( { schema, responseErrors, expectedErrors } ) => {
			const {
				getAllValidationErrors,
				setValidationErrorsFromSubmitResponse
			} = withSetup( schema );

			setValidationErrorsFromSubmitResponse( {
				errors: responseErrors,
				docref: '...'
			} );
			const actualErrors = getAllValidationErrors();

			expect( actualErrors ).toStrictEqual( expectedErrors );
		} );

	} );

	describe( 'getValidationErrorMessageForFormFieldId', () => {
		it( 'picks the correct message for the given pointer', () => {
			const {
				getValidationErrorMessageForFormFieldId
			} = withArrayErrors();

			const actualErrorMessage = getValidationErrorMessageForFormFieldId( 'GEHelpPanelLinks.3.title' );

			expect( actualErrorMessage ).toBe( 'The property title is required' );
		} );

		it( 'returns `null` if there is no match for the given pointer', () => {
			const {
				getValidationErrorMessageForFormFieldId
			} = withArrayErrors();

			const actualErrorMessage = getValidationErrorMessageForFormFieldId( 'GEHelpPanelLinks.0.title' );

			expect( actualErrorMessage ).toBe( null );
		} );
	} );

	describe( 'adjustValidationErrorsOnArrayItemDelete', () => {
		it( 'removes the errors for the deleted array item', () => {
			const {
				adjustValidationErrorsOnArrayItemDelete,
				getValidationErrorMessageForFormFieldId
			} = withArrayErrors();

			adjustValidationErrorsOnArrayItemDelete( 'GEHelpPanelLinks', 3 );
			const actualErrorMessage = getValidationErrorMessageForFormFieldId( 'GEHelpPanelLinks.3.title' );

			expect( actualErrorMessage ).toBe( null );
		} );

		it( 'adjust the errors for the array items following the deleted item', () => {
			const {
				adjustValidationErrorsOnArrayItemDelete,
				getValidationErrorMessageForFormFieldId
			} = withArrayErrors();

			adjustValidationErrorsOnArrayItemDelete( 'GEHelpPanelLinks', 2 );
			const actualErrorMessage = getValidationErrorMessageForFormFieldId( 'GEHelpPanelLinks.2.title' );

			expect( actualErrorMessage ).toBe( 'The property title is required' );
		} );
	} );

	describe( 'setValidationErrorForFieldId', () => {
		it( 'adds a new error', () => {
			const {
				setValidationErrorForFieldId,
				clearValidationErrorForFieldId,
				getAllValidationErrors
			} = withSetup( {
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
			} );

			setValidationErrorForFieldId( 'GEHelpPanelLinks.0.text', 'bar' );
			const actualErrors = getAllValidationErrors();

			expect( actualErrors ).toStrictEqual( [
				{
					formFieldId: 'GEHelpPanelLinks.0.text',
					formFieldLabels: [
						mw.message( 'prefix-gehelppanellinks-label' ),
						mw.message( 'prefix-gehelppanellinks-item-label: 1' ),
						mw.message( 'prefix-gehelppanellinks-text-label' )
					],
					messageLiteral: 'bar'
				}
			] );

			clearValidationErrorForFieldId( 'GEHelpPanelLinks.0.text' );
			const actualErrorsAfterClear = getAllValidationErrors();
			expect( actualErrorsAfterClear ).toStrictEqual( [] );
		} );
	} );
} );
