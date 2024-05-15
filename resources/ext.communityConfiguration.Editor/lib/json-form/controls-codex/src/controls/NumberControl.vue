<template>
	<control-wrapper v-bind="controlWrapper">
		<cdx-text-input
			v-bind="control.otherAttrs"
			v-model="control.modelValue"
			input-type="number"
			:min="control.schema.minimum || null"
			:max="control.schema.maximum || null"
			:placeholder="control.uischema.placeholder"
			@update:model-value="onChange"
		>
		</cdx-text-input>
	</control-wrapper>
</template>

<script>
const { defineComponent } = require( 'vue' );
const { CdxTextInput } = require( '@wikimedia/codex' );
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
		return Object.assign( {}, codexControl, {
			onChange( newVal ) {
				if ( newVal === '' ) {
					newVal = undefined;
				}
				codexControl.onChange( newVal );
			}
		} );
	}
} );
</script>
