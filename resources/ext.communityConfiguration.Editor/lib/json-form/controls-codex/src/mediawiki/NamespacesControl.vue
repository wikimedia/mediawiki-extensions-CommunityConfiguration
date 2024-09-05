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
			@keydown="onKeyDown"
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
const { ref, unref, inject } = require( 'vue' );
const { CdxChipInput, CdxMenu } = require( '../../../../../../codex.js' );
const {
	rendererProps,
	useJsonFormControl,
} = require( '../../config/index.js' );
const { debounce, useCodexControl } = require( '../utils.js' );
const ControlWrapper = require( '../controls/ControlWrapper.vue' );
const filterSelection = ( selection ) => ( item ) => selection.map( ( x ) => x.value ).indexOf( item.value ) === -1;

const filterSearchQuery = ( searchQuery ) => ( item ) => {
	if ( !searchQuery ) {
		return false;
	}
	return item.value.startsWith( searchQuery ) ||
		item.value.startsWith( searchQuery.toLowerCase() );
};

// @vue/component
module.exports = exports = {
	name: 'NamespacesControl',
	components: {
		CdxChipInput,
		CdxMenu,
		ControlWrapper,
	},
	props: Object.assign( {}, rendererProps(), {} ),
	setup( props ) {
		const EDITOR_FORM_CONFIG = inject( 'EDITOR_FORM_CONFIG' );
		const formattedNamespaces = EDITOR_FORM_CONFIG.namespaceSelectorOptions;
		const namespaceToMenuItem = ( value ) => ( { value: formattedNamespaces[ value ] } );
		const findNamespaceByName = ( name ) => {
			for ( const ns in formattedNamespaces ) {
				if ( formattedNamespaces[ ns ] === name ) {
					// formattedNamespaces keys are strings, convert them to numbers
					return +ns;
				}
			}
		};
		const menuItemToNamespace = ( { value } ) => findNamespaceByName( value );
		const NS_MENU_ITEMS = Object.keys( formattedNamespaces ).map( namespaceToMenuItem );
		const {
			control,
			controlWrapper,
			onChange,
		} = useCodexControl( useJsonFormControl( props ) );
		const input = ref();
		const selectedValue = ref( null );
		const currentSearchTerm = ref( '' );
		const expanded = ref( false );
		const initialValue = unref( control.modelValue ).map( namespaceToMenuItem );
		const selection = ref( initialValue );
		const menuItems = ref( NS_MENU_ITEMS.filter( filterSelection( initialValue ) ) );

		/**
		 * Handle keyup input.
		 *
		 * @param {string} value
		 */
		const onKeyDown = debounce( ( evt ) => {
			const value = evt.srcElement.value;
			// Internally track the current search term.
			currentSearchTerm.value = value;

			// Restore initial list if we have no input.
			if ( !value ) {
				menuItems.value = NS_MENU_ITEMS.filter( filterSelection( selection.value ) );
				return;
			} else {
				menuItems.value = menuItems.value.filter( filterSearchQuery( value ) );
			}
			expanded.value = true;
		}, 300 );

		return {
			controlWrapper,
			expanded,
			input,
			initialValue,
			menuItems,
			onKeyDown,
			selection,
			// Not read as we use an update event handler onItemSelected instead of watching
			// selectedValue. Needed to avoid CdxMenu prop validation warnings.
			selectedValue,
			onBlur() {
				expanded.value = false;
				currentSearchTerm.value = '';
			},
			onChipSelectionChange( newVal ) {
				// map ChipInputItem model back to config model
				onChange( newVal.map( menuItemToNamespace ) );
				menuItems.value = NS_MENU_ITEMS.filter( filterSelection( newVal ) );
			},
			onItemSelected( itemValue ) {
				selection.value = [ ...selection.value, { value: itemValue } ];
				onChange( selection.value.map( menuItemToNamespace ) );
				menuItems.value = NS_MENU_ITEMS.filter( filterSelection( selection.value ) );
				currentSearchTerm.value = '';
				// HACK, better way to remove the user typed text in ChipInput?
				input.value.inputValue = '';
			},
		};
	},
};
</script>
