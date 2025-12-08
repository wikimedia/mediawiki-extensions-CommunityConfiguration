describe( 'Community Configuration Example Page', () => {

	before( function () {
		cy.request( {
			url: 'api.php',
			qs: {
				format: 'json',
				action: 'query',
				meta: 'siteinfo',
				siprop: 'extensions',
			},
		} ).then( ( response ) => {
			if ( !response.body.query.extensions.some( ( ext ) => ext.name === 'CommunityConfigurationExample' ) ) {
				this.skip();
			}
		} );
	} );

	context( 'Form elements and basic functionality', () => {

		beforeEach( () => {
			cy.visit( 'index.php?title=Special:CommunityConfiguration/CommunityConfigurationExample&uselang=qqx' );
		} );

		it( 'should have all expected form elements and labels', () => {
			const fields = [
				'CCExample_String',
				'CCExample_Numbers',
				'CCExample_FavoriteColors',
			];
			fields.forEach( ( field ) => {
				cy.get( `#${ field }` ).should( 'exist' );
			} );

			cy.get( '.communityconfiguration-info-section' )
				.should( 'exist' )
				.and( 'contain', '(communityconfiguration-communityconfigurationexample-info-text)' );

			cy.contains( 'label', '(communityconfiguration-communityconfigurationexample-ccexample_string-label)' ).should( 'exist' );
			cy.contains( 'legend', '(communityconfiguration-communityconfigurationexample-ccexample_numbers-label)' ).should( 'exist' );
			cy.contains( 'label', '(communityconfiguration-communityconfigurationexample-ccexample_numbers-integernumber-label)' ).should( 'exist' );
			cy.contains( 'label', '(communityconfiguration-communityconfigurationexample-ccexample_numbers-decimalnumber-label)' ).should( 'exist' );
			cy.contains( 'label', '(communityconfiguration-communityconfigurationexample-ccexample_favoritecolors-label)' ).should( 'exist' );

			cy.contains( '(communityconfiguration-communityconfigurationexample-ccexample_string-description)' ).should( 'exist' );
			cy.contains( '(communityconfiguration-communityconfigurationexample-ccexample_numbers-description)' ).should( 'exist' );
			cy.contains( '(communityconfiguration-communityconfigurationexample-ccexample_numbers-integernumber-description)' ).should( 'exist' );
			cy.contains( '(communityconfiguration-communityconfigurationexample-ccexample_numbers-decimalnumber-description)' ).should( 'exist' );
			cy.contains( '(communityconfiguration-communityconfigurationexample-ccexample_favoritecolors-description)' ).should( 'exist' );

			cy.contains( '(communityconfiguration-communityconfigurationexample-ccexample_string-help-text)' ).should( 'exist' );
			cy.contains( '(communityconfiguration-communityconfigurationexample-ccexample_numbers-integernumber-help-text)' ).should( 'exist' );
			cy.contains( '(communityconfiguration-communityconfigurationexample-ccexample_numbers-decimalnumber-help-text)' ).should( 'exist' );
			cy.contains( '(communityconfiguration-communityconfigurationexample-ccexample_favoritecolors-help-text)' ).should( 'exist' );
		} );

		it( 'should have a save button', () => {
			cy.get( 'button' ).contains( '(communityconfiguration-editor-form-submit-button-text)' ).should( 'exist' );
		} );

		it( 'should have a disabled save button for logged-out users', () => {
			cy.clearCookies();
			cy.reload();

			cy.get( 'button' ).contains( '(communityconfiguration-editor-form-submit-button-text)' ).should( 'be.disabled' );

			cy.get( '.ext-communityConfiguration-FooterPermissionsMessage' )
				.should( 'contain', '(communityconfiguration-editor-client-notice-footer-message)' );
		} );

		it( 'should update a simple string via API and verify the update on the form', () => {
			const updatedString = 'Updated simple string';

			cy.task( 'MwApi:UpdateConfiguration', {
				provider: 'CommunityConfigurationExample',
				configData: {
					// eslint-disable-next-line camelcase
					CCExample_String: updatedString,
				},
				summary: 'Automated test update of a simple string.',
			} ).then( () => {
				cy.reload();
				cy.get( '#CCExample_String input[type="text"]' ).should( 'have.value', updatedString );
			} );
		} );
	} );

	it( 'should save configuration changes and verify them on the example page', () => {
		cy.loginViaApi();

		cy.visit( 'index.php?title=Special:CommunityConfiguration/CommunityConfigurationExample' );

		const testString = `Test string ${ Date.now() }`;

		cy.get( '#CCExample_String input[type="text"]' ).clear();
		cy.get( '#CCExample_String input[type="text"]' ).type( testString );

		cy.get( 'button' ).contains( 'Save changes' ).should( 'not.be.disabled' ).click();

		cy.get( '.cdx-dialog[role="dialog"]' ).should( 'be.visible' );

		cy.get( '.cdx-dialog__header__title' ).should( 'contain', 'Save changes for CommunityConfiguration Example' );

		cy.get( '[data-testid="edit-summary-text-area"]' )
			.should( 'have.attr', 'placeholder', 'Describe the changes that were made in the configuration' )
			.type( 'Automated test: Saving configuration changes' );

		cy.get( '.cdx-dialog__footer__actions .cdx-button--action-progressive' )
			.contains( 'Save changes' )
			.click();

		cy.get( '.cdx-dialog[role="dialog"]' ).should( 'not.exist' );

		cy.contains( 'Your changes were saved' ).should( 'be.visible' );

		cy.visit( 'index.php?title=Special:CommunityConfigurationExample' );
		cy.contains( testString ).should( 'exist' );
	} );
} );
