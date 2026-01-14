<template>
	<control-wrapper v-bind="controlWrapper">
		<cdx-multiselect-lookup
			v-model:input-chips="chips"
			v-model:selected="selection"
			v-model:input-value="inputValue"
			:placeholder="$i18n( 'mw-widgets-titlesmultiselect-placeholder' ).text()"
			:chip-aria-description="$i18n( 'communityconfiguration-editor-chip-control-aria-chip-description' ).text()"
			remove-button-label="remove"
			:menu-items="menuItems"
			@update:input-value="onInput"
			@update:selected="onTitlesUpdated"
		></cdx-multiselect-lookup>
	</control-wrapper>
</template>

<script>
const { ref, unref } = require( 'vue' );
const { CdxMultiselectLookup } = require( '../../../../../../codex.js' );
const {
	rendererProps,
	useJsonFormControl,
} = require( '../../config/index.js' );
const { debounce, useCodexControl } = require( '../utils.js' );
const { search } = require( './api.js' );
const ControlWrapper = require( '../controls/ControlWrapper.vue' );
const filterSelection = ( selection ) => ( item ) => selection.map( ( x ) => x.value ).indexOf( item.value ) === -1;

// @vue/component
module.exports = exports = {
	name: 'PageTitlesControl',
	components: {
		CdxMultiselectLookup,
		ControlWrapper,
	},
	props: Object.assign( {}, rendererProps(), {} ),
	setup( props ) {
		const {
			control,
			controlWrapper,
			onChange,
		} = useCodexControl( useJsonFormControl( props ) );
		const inputValue = ref( '' );
		const menuItems = ref( [] );
		const currentSearchTerm = ref( '' );
		const initialValueChips = unref( control.modelValue ).map( ( pageTitle ) => ( {
			label: pageTitle,
			value: pageTitle,
		} ) );
		const initialValueSelection = unref( control.modelValue ).map( ( pageTitle ) => ( pageTitle ) );
		const selection = ref( initialValueSelection );
		const chips = ref( initialValueChips );
		/**
		 * Handle onInput.
		 *
		 * @param {string} value
		 */
		const onInput = debounce( ( value ) => {
			// Internally track the current search term.
			currentSearchTerm.value = value;

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

					// Add the user input as a menu item if there are no results.
					// This supports custom input.
					if ( !data.query || data.query.prefixsearch.length === 0 ) {
						menuItems.value = [ {
							label: value,
							value: value,
						} ];
						return;
					}

					// Build an array of menu items.
					const results = data.query.prefixsearch.map( ( result ) => ( {
						label: result.title,
						value: result.title,
					} ) );
					// Check input not present in results.  If it is not present, add it as a menuItem.
					const inputMissing = results.findIndex( ( template ) => template.value === value ) === -1;
					if ( inputMissing ) {
						results.unshift( { value, label: value } );
					}

					// Update menuItems.
					menuItems.value = results.filter( filterSelection( selection.value ) );
				} )
				.catch( () => {
					// On error, set results to empty.
					menuItems.value = [];
				} );
		}, 300 );

		return {
			controlWrapper,
			chips,
			selection,
			menuItems,
			inputValue,
			onInput,
			onTitlesUpdated( newTitles ) {
				onChange( newTitles );
			},
		};
	},
};
</script>
