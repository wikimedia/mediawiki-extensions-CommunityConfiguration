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
			:highlight-query="true"
			@update:input-value="onInput"
			@update:selected="onNamespacesUpdated"
			@focus="() => onInput( inputValue )"
		>
			<template #no-results>
				{{ $i18n( 'communityconfiguration-page-title-control-no-results' ).text() }}
			</template>
		</cdx-multiselect-lookup>
	</control-wrapper>
</template>

<script>
const { ref, unref, inject } = require( 'vue' );
const { CdxMultiselectLookup } = require( '../../../../../../codex.js' );
const {
	rendererProps,
	useJsonFormControl,
} = require( '../../config/index.js' );
const { useCodexControl } = require( '../utils.js' );
const ControlWrapper = require( '../controls/ControlWrapper.vue' );

const filterSearchQuery = ( searchQuery ) => ( item ) => {
	if ( !searchQuery ) {
		return false;
	}
	return item.label.toLowerCase().startsWith( searchQuery.toLowerCase() );
};

// @vue/component
module.exports = exports = {
	name: 'NamespacesControl',
	components: {
		CdxMultiselectLookup,
		ControlWrapper,
	},
	props: Object.assign( {}, rendererProps(), {} ),
	setup( props ) {
		const EDITOR_FORM_CONFIG = inject( 'EDITOR_FORM_CONFIG' );
		const formattedNamespaces = EDITOR_FORM_CONFIG.namespaceSelectorOptions;
		const namespaceToMenuItem = ( value ) => ( {
			value: Number( value ),
			label: formattedNamespaces[ value ],
		} );

		const NS_MENU_ITEMS = Object.keys( formattedNamespaces ).map( namespaceToMenuItem );
		const {
			control,
			controlWrapper,
			onChange,
		} = useCodexControl( useJsonFormControl( props ) );
		const inputValue = ref( '' );
		const initialValueSelection = unref( control.modelValue );
		const initialValueChips = initialValueSelection.map( ( item ) => ( {
			label: formattedNamespaces[ item ],
			value: item,
		} ) );
		const chips = ref( initialValueChips );
		const selection = ref( initialValueSelection );
		const menuItems = ref( NS_MENU_ITEMS );

		/**
		 * Handle onInput
		 *
		 * @param {string} value
		 */
		const onInput = ( value ) => {
			// Restore initial list if we have no input.
			if ( !value ) {
				menuItems.value = NS_MENU_ITEMS;
			} else {
				menuItems.value = NS_MENU_ITEMS.filter( filterSearchQuery( value ) );
			}
		};

		return {
			controlWrapper,
			inputValue,
			menuItems,
			onInput,
			chips,
			selection,
			onNamespacesUpdated( updatedNamespaces ) {
				onChange( updatedNamespaces );
				menuItems.value = [];
			},
		};
	},
};
</script>
