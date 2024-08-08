<template>
	<control-wrapper
		v-bind="controlWrapper"
	>
		<cdx-chip-input
			ref="chipInput"
			v-model:input-chips="chips"
			:placeholder="$i18n( 'mw-widgets-titlesmultiselect-placeholder' ).text()"
			:chip-aria-description="$i18n( 'communityconfiguration-editor-chip-control-aria-chip-description' ).text()"
			:aria-activedescendant="activeDescendant"
			@click="onClick"
			@blur="expanded = false"
			@keydown="onKeydown"
			@update:input-chips="handleChipChange"
		></cdx-chip-input>
		<cdx-menu
			:id="menuId"
			ref="menu"
			v-model:selected="selectedValue"
			v-model:expanded="expanded"
			:menu-items="menuItems"
			@update:selected="handleSelection"
		></cdx-menu>
	</control-wrapper>
</template>

<script>
const { defineComponent, ref, computed, inject } = require( 'vue' );
const { CdxChipInput, CdxMenu, useFloatingMenu, useGeneratedId } = require( '../../../../../../codex.js' );
const ControlWrapper = require( '../controls/ControlWrapper.vue' );
const { rendererProps, useJsonFormControl, useValidationErrors } = require( '../../config/index.js' );
const { useCodexControl } = require( '../utils.js' );

module.exports = exports = defineComponent( {
	name: 'MultiselectEnumControl',
	components: {
		CdxChipInput,
		CdxMenu,
		ControlWrapper
	},
	props: Object.assign( {}, rendererProps(), {} ),
	setup( props ) {
		const i18n = inject( 'i18n' );
		const {
			control,
			controlWrapper,
			onChange
		} = useCodexControl( useJsonFormControl( props ) );
		const chipInput = ref();
		const menu = ref();
		useFloatingMenu( chipInput, menu );
		const expanded = ref( false );
		const activeDescendant = computed( () => {
			const highlightedItem = menu.value && menu.value.getHighlightedMenuItem();
			return highlightedItem ? highlightedItem.id : undefined;
		} );
		const menuId = useGeneratedId( 'menu' );

		const allowedValues = control.schema.items.enum;
		const menuItems = allowedValues.map( ( enumKey ) => ( {
			value: enumKey,
			label: i18n( control.uischema.enumLabels[ enumKey ] ).text()
		} ) );

		const initialChips = control.modelValue.value.map( ( value ) => ( { value } ) );
		const chips = ref( initialChips );
		const selectedValue = ref( control.modelValue.value );

		const { setValidationErrorForFieldId, clearValidationErrorForFieldId } = useValidationErrors();
		function validateNewValues( newValues ) {
			// eslint-disable-next-line es-x/no-array-prototype-includes
			if ( !newValues.every( ( value ) => allowedValues.includes( value ) ) ) {
				setValidationErrorForFieldId(
					controlWrapper.id,
					i18n(
						'communityconfiguration-editor-error-validation-enum-invalid-value',
						allowedValues.join( ', ' )
					).text()
				);
				return;
			}

			if ( control.schema.maxItems && newValues.length > control.schema.maxItems ) {
				setValidationErrorForFieldId(
					controlWrapper.id,
					i18n( 'communityconfiguration-editor-error-validation-array-items-max', control.schema.maxItems ).text()
				);
				return;
			}

			if ( control.schema.minItems && newValues.length < control.schema.minItems ) {
				setValidationErrorForFieldId(
					controlWrapper.id,
					i18n( 'communityconfiguration-editor-error-validation-array-items-min', control.schema.minItems ).text()
				);
				return;
			}

			clearValidationErrorForFieldId( controlWrapper.id );
		}

		function updateState( newValues ) {
			validateNewValues( newValues );
			onChange( newValues );
		}

		function handleChipChange( newChips ) {
			const chipValues = newChips.map( ( chip ) => chip.value );
			selectedValue.value = chipValues;
			updateState( chipValues );
		}

		function handleSelection( newSelected ) {
			chips.value = newSelected.map( ( value ) => ( { value } ) );
			updateState( newSelected );
		}

		/**
		 * Delegate most keydowns on the text input to the Menu component. This
		 * allows the Menu component to enable keyboard navigation of the menu.
		 *
		 * @param {KeyboardEvent} e The keyboard event
		 */
		function onKeydown( e ) {
			// The menu component enables the space key to open and close the
			// menu. However, for text inputs with menus, the space key should
			// always insert a new space character in the input.
			if ( e.key === ' ' ) {
				return;
			}

			// Delegate all other key events to the Menu component.
			if ( menu.value ) {
				menu.value.delegateKeyNavigation( e );
			}
		}

		function onClick() {
			expanded.value = true;
		}

		return {
			controlWrapper,
			chipInput,
			menu,
			expanded,
			activeDescendant,
			menuId,
			menuItems,
			chips,
			selectedValue,
			handleChipChange,
			handleSelection,
			onKeydown,
			onClick
		};
	}
} );
</script>
