const { buildUISchema } = require( '../uischema.js' );
const testJsonSchema = require( './test-json-schema.json' );
const editorConfig = {
	i18nPrefix: 'testenvironment-someprovider'
};
const testJsonConfig = {
	ExampleString: 'Some string',
	ExampleArray: [
		'Some string',
		'Some other string'
	]
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
		`testenvironment-someprovider-${subschemaName.toLowerCase()}-label`
	);
	// TODO assert control label and help text
}

function assertUISchemaArrayDefaults( subschemaName, uischema ) {
	const uiSchemaElement = findUISchemaElement( subschemaName, uischema.elements );
	expect( uiSchemaElement.labels ).toBeDefined();
	uiSchemaElement.labels.forEach( ( label, index ) => {
		expect( label.text() ).toBe(
			`testenvironment-someprovider-${subschemaName.toLowerCase()}-${index}-label`
		);
		// TODO assert control label and help text
	} );
}

describe( 'UISchema', () => {
	beforeAll( () => {
		global.mw.messages = [
			'testenvironment-someprovider-examplestring-label',
			'testenvironment-someprovider-examplearray-label',
			'testenvironment-someprovider-examplearray-0-label',
			'testenvironment-someprovider-examplearray-1-label'
		];
		global.mw.Message = jest.fn( ( messages, key ) => ( {
			exists: jest.fn( () => messages.indexOf( key ) !== -1 ),
			text: jest.fn( () => key ),
			parse: jest.fn( () => key )
		} ) );
	} );

	it( 'should produce a UI schema given a Json schema', () => {
		const actual = buildUISchema( testJsonSchema, editorConfig, '', testJsonConfig );
		// Assert an element is produced for each top schema property
		expect( actual.elements.length ).toEqual(
			Object.keys( testJsonSchema.properties ).length
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
} );
