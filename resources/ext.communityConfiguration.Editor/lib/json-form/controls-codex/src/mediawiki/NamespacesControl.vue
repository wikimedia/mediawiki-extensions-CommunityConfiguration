<template>
	<control-wrapper v-bind="controlWrapper">
		<cdx-chip-input
			ref="input"
			v-model:input-chips="selection"
			:initial-value="initialValue"
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
const { ref, unref } = require( 'vue' );
const { CdxChipInput, CdxMenu } = require( '@wikimedia/codex' );
const {
	rendererProps,
	useJsonFormControl
} = require( '../../config/index.js' );
const { debounce, useCodexControl } = require( '../utils.js' );
const ControlWrapper = require( '../controls/ControlWrapper.vue' );
const formattedNamespaces = mw.config.get( 'wgFormattedNamespaces' );
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
const filterSelection = ( selection ) => ( item ) => {
	return selection.map( ( x ) => x.value ).indexOf( item.value ) === -1;
};
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
		ControlWrapper
	},
	props: Object.assign( {}, rendererProps(), {} ),
	setup( props ) {
		const {
			control,
			controlWrapper,
			onChange
		} = useCodexControl( useJsonFormControl( props ) );
		const input = ref();
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
		const onKeyDown = debounce( function ( evt ) {
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
			onBlur() {
				expanded.value = false;
				currentSearchTerm.value = '';
			},
			onChipSelectionChange( newVal ) {
				// map ChipInputItem model back to config model
				onChange( newVal.map( menuItemToNamespace ) );
			},
			onItemSelected( itemValue ) {
				selection.value = [ ...selection.value, { value: itemValue } ];
				onChange( selection.value.map( menuItemToNamespace ) );
				currentSearchTerm.value = '';
				// HACK, bettwer way to remove the user typed text in ChipInput?
				input.value.inputValue = '';
			}
		};
	}
};
</script>
