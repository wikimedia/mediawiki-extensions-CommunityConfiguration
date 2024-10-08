<template>
	<control-wrapper v-bind="controlWrapper">
		<cdx-chip-input
			ref="input"
			v-model:input-chips="selection"
			:initial-value="initialValue"
			:placeholder="$i18n( 'mw-widgets-titlesmultiselect-placeholder' ).text()"
			:chip-aria-description="$i18n( 'communityconfiguration-editor-chip-control-aria-chip-description' ).text()"
			remove-button-label="remove"
			@blur="onBlur"
			@focus="expanded = true"
			@update:input-chips="onChipSelectionChange"
		></cdx-chip-input>
		<cdx-menu
			v-model:selected="selectedValue"
			v-model:expanded="expanded"
			:menu-items="menuItems"
			@update:selected="onItemSelected"
		></cdx-menu>
	</control-wrapper>
</template>

<script>
const { ref, unref, onMounted } = require( 'vue' );
const { CdxChipInput, CdxMenu } = require( '../../../../../../codex.js' );
const {
	rendererProps,
	useJsonFormControl,
} = require( '../../config/index.js' );
const { debounce, useCodexControl } = require( '../utils.js' );
const { search } = require( './api.js' );
const ControlWrapper = require( '../controls/ControlWrapper.vue' );
const chipToPageTitle = ( { value } ) => value;
const filterSelection = ( selection ) => ( item ) => selection.map( ( x ) => x.value ).indexOf( item.value ) === -1;

// @vue/component
module.exports = exports = {
	name: 'PageTitlesControl',
	components: {
		CdxChipInput,
		CdxMenu,
		ControlWrapper,
	},
	props: Object.assign( {}, rendererProps(), {} ),
	setup( props ) {
		const {
			control,
			controlWrapper,
			onChange,
		} = useCodexControl( useJsonFormControl( props ) );
		const input = ref();
		const selectedValue = ref( null );
		const expanded = ref( false );
		const menuItems = ref( [] );
		const currentSearchTerm = ref( '' );
		const initialValue = unref( control.modelValue ).map( ( pageTitle ) => ( {
			value: pageTitle,
			label: pageTitle,
		} ) );
		const selection = ref( initialValue );

		/**
		 * Handle onKeyUp.
		 *
		 * @param {string} value
		 */
		const onKeyUp = debounce( ( evt ) => {
			const value = evt.srcElement.value;
			// Internally track the current search term.
			currentSearchTerm.value = value;

			// Do nothing if we have no input.
			if ( !value ) {
				menuItems.value = [];
				expanded.value = false;
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
					menuItems.value = results.filter( filterSelection( selection.value ) );
					expanded.value = true;
				} )
				.catch( () => {
					// On error, set results to empty.
					menuItems.value = [];
				} );
		}, 300 );

		onMounted( () => {
			input.value.input.addEventListener( 'keyup', onKeyUp );
		} );

		return {
			controlWrapper,
			expanded,
			input,
			selection,
			// Not read as we use an updaet event handler onItemSelected instead of watching
			// selectedValue. Needed to avoid CdxMenu prop validation warnings.
			selectedValue,
			initialValue,
			menuItems,
			onBlur() {
				expanded.value = false;
				currentSearchTerm.value = '';
			},
			onChipSelectionChange( newVal ) {
				// map ChipInputItem model back to config model
				onChange( newVal.map( chipToPageTitle ) );
			},
			onItemSelected( itemValue ) {
				const index = menuItems.value.findIndex( ( menuItem ) => menuItem.value === itemValue );
				selection.value = [ ...selection.value, {
					label: menuItems.value[ index ].label,
					value: menuItems.value[ index ].value,
				} ];
				onChange( selection.value.map( chipToPageTitle ) );
				currentSearchTerm.value = '';
				// HACK, bettwer way to remove the user typed text in ChipInput?
				input.value.inputValue = '';
			},
		};
	},
};

</script>
