<template>
	<control-wrapper v-bind="controlWrapper">
		<cdx-lookup
			v-model:selected="selection"
			v-model:input-value="inputValue"
			:menu-items="menuItems"
			:menu-config="menuConfig"
			:placeholder="uischema.placeholder"
			@input="onInput"
			@update:selected="onSelectionChange"
		>
			<template #no-results>
				{{ $i18n( 'communityconfiguration-page-title-control-no-results' ).text() }}
			</template>
		</cdx-lookup>
	</control-wrapper>
</template>

<script>
const { ref, unref } = require( 'vue' );
const { CdxLookup } = require( '../../../../../../codex.js' );
const {
	rendererProps,
	useJsonFormControl,
} = require( '../../config/index.js' );
const { search } = require( './api.js' );
const { debounce, useCodexControl } = require( '../utils.js' );
const ControlWrapper = require( '../controls/ControlWrapper.vue' );

// @vue/component
module.exports = exports = {
	name: 'PageTitleControl',
	components: {
		CdxLookup,
		ControlWrapper,
	},
	props: Object.assign( {}, rendererProps(), {} ),
	setup( props ) {
		const {
			control,
			controlWrapper,
			onChange,
		} = useCodexControl( useJsonFormControl( props ) );
		const inputValue = ref( unref( control.modelValue ) || '' );
		const selection = ref( null );
		const menuItems = ref( [] );
		const currentSearchTerm = ref( '' );

		/**
		 * Handles changes in the selected value from the dropdown.
		 *
		 * @param {string|null} value - The selected menu item value, null if the selection does not match any entry in
		 * the menu items.
		 */
		const onSelectionChange = ( value ) => {
			selection.value = value;
			if ( value !== null ) {
				// A dropdown item was selected
				inputValue.value = value;
				currentSearchTerm.value = value;
				onChange( value );
			}
			// If value is null, we don't update inputValue or currentSearchTerm,
			// preserving the user's typed input
		};

		/**
		 * Handle lookup input.
		 *
		 * @param {string} value
		 */
		const onInput = debounce( ( value ) => {
			// Always update inputValue and currentSearchTerm with user input
			inputValue.value = value;
			currentSearchTerm.value = value;

			// If the input doesn't match the current selection, clear the selection
			if ( value !== selection.value ) {
				selection.value = null;
			}
			onChange( value );

			// Do nothing if we have no input.
			if ( !value ) {
				menuItems.value = [];
				return;
			}

			search( value )
				.then( ( data ) => {
					// Make sure this data is still relevant first.
					if ( currentSearchTerm.value !== value ) {
						return;
					}

					// Reset the menu items if there are no results.
					if ( !data.query || data.query.prefixsearch.length === 0 ) {
						menuItems.value = [];
						return;
					}

					// Build an array of menu items.
					const results = data.query.prefixsearch.map( ( result ) => ( {
						label: result.title,
						value: result.title,
					} ) );

					// Update menuItems.
					menuItems.value = results;
				} )
				.catch( () => {
					// On error, set results to empty.
					menuItems.value = [];
				} );
		}, 300 );

		// do an initial search for menu-items to prepopulate the select field
		if ( inputValue.value ) {
			onInput( inputValue.value );
		}

		const menuConfig = {
			visibleItemLimit: 6,
		};

		return {
			onSelectionChange,
			controlWrapper,
			selection,
			inputValue,
			menuItems,
			menuConfig,
			onInput,
			uischema: control.uischema,
		};
	},
};
</script>
