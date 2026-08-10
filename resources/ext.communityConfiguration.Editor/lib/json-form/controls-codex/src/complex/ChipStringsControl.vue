<template>
	<control-wrapper
		v-bind="controlWrapper"
	>
		<cdx-chip-input
			v-model:input-chips="inputChips"
			:placeholder="$i18n( 'mw-widgets-titlesmultiselect-placeholder' ).text()"
			:chip-aria-description="$i18n( 'communityconfiguration-editor-chip-control-aria-chip-description' ).text()"
			@update:input-chips="onChipUpdate"
		></cdx-chip-input>
	</control-wrapper>
</template>

<script>
const { ref } = require( 'vue' );
const { CdxChipInput } = require( '../../../../../../codex.js' );
const ControlWrapper = require( '../controls/ControlWrapper.vue' );
const { rendererProps, useCodexControl, useJsonFormControl } = require( 'ext.communityConfiguration.Editor.controls' );

// @vue/component
module.exports = exports = {
	name: 'ChipStringsControl',
	components: {
		CdxChipInput,
		ControlWrapper,
	},
	props: Object.assign( {}, rendererProps(), {} ),
	setup( props ) {
		const {
			control,
			controlWrapper,
			onChange,
		} = useCodexControl( useJsonFormControl( props ) );
		const inputChips = ref( control.modelValue.value.map( ( value ) => ( {
			value,
		} ) ) );
		function onChipUpdate( chips ) {
			onChange( chips.map( ( chip ) => chip.value ) );
		}
		return {
			controlWrapper,
			inputChips,
			onChipUpdate,
		};
	},
};
</script>
