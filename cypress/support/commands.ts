// ***********************************************
// This example commands.js shows you how to
// create various custom commands and overwrite
// existing commands.
//
// For more comprehensive examples of custom
// commands please read more here:
// https://on.cypress.io/custom-commands
// ***********************************************
//
//
// -- This is a parent command --
// Cypress.Commands.add('login', (email, password) => { ... })
//
//
// -- This is a child command --
// Cypress.Commands.add('drag', { prevSubject: 'element'}, (subject, options) => { ... })
//
//
// -- This is a dual command --
// Cypress.Commands.add('dismiss', { prevSubject: 'optional'}, (subject, options) => { ... })
//
//
// -- This will overwrite an existing command --
// Cypress.Commands.overwrite('visit', (originalFn, url, options) => { ... })
Cypress.Commands.add( 'loginViaApi', (): void => {
	const config = Cypress.env();
	cy.visit( '/index.php' );
	cy.window().should( 'have.property', 'mw' );
	cy.window().its( 'mw' ).should( 'have.property', 'Api' );
	cy.window().its( 'mw' ).then( async ( mw ): Promise<void> => {
		const api = new mw.Api();
		await api.login(
			config.mediawikiAdminUsername,
			config.mediawikiAdminPassword,
		);
	} );
} );

/* eslint-disable @typescript-eslint/no-namespace */
declare global {
	namespace Cypress {
		interface Chainable {
			loginViaApi(): Chainable<JQuery<HTMLElement>>;
		}
	}
}

export {};
