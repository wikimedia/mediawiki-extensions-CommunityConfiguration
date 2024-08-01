<template>
	<control-wrapper v-bind="controlWrapper">
		<cdx-text-input
			v-bind="control.otherAttrs"
			v-model="control.modelValue.value"
			input-type="number"
			:min="min"
			:max="max"
			:placeholder="control.uischema.placeholder"
			@update:model-value="onChange"
		>
		</cdx-text-input>
	</control-wrapper>
</template>

<script>
const { defineComponent } = require( 'vue' );
const { CdxTextInput } = require( '../../../../../../codex.js' );
const {
	rendererProps,
	useJsonFormControl
} = require( '../../config/index.js' );
const { useCodexControl } = require( '../utils.js' );
const ControlWrapper = require( './ControlWrapper.vue' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'NumberControl',
	components: {
		CdxTextInput,
		ControlWrapper
	},
	props: Object.assign( {}, rendererProps(), {} ),
	setup( props ) {
		const codexControl = useCodexControl( useJsonFormControl( props ) );

		const min = ( typeof codexControl.control.schema.minimum ) === 'number' ?
			codexControl.control.schema.minimum :
			null;
		const max = ( typeof codexControl.control.schema.maximum ) === 'number' ?
			codexControl.control.schema.maximum :
			null;

		return Object.assign( {}, codexControl, {
			onChange( newVal ) {
				if ( newVal === '' ) {
					newVal = undefined;
				}
				codexControl.onChange( newVal );
			},
			min,
			max
		} );
	}
} );
</script>
