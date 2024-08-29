<template>
	<control-wrapper v-bind="controlWrapper">
		<cdx-text-input
			v-bind="control.otherAttrs"
			v-model="control.modelValue.value"
			input-type="number"
			:min="min"
			:max="max"
			:step="step"
			:placeholder="control.uischema.placeholder"
			@update:model-value="onChange"
			@input="onInput"
		>
		</cdx-text-input>
	</control-wrapper>
</template>

<script>
const { defineComponent } = require( 'vue' );
const { CdxTextInput } = require( '../../../../../../codex.js' );
const {
	rendererProps,
	useJsonFormControl,
	useValidationErrors,
} = require( '../../config/index.js' );
const { useCodexControl } = require( '../utils.js' );
const ControlWrapper = require( './ControlWrapper.vue' );

function getStepFromSchema( schema ) {
	if ( schema.multipleOf ) {
		return schema.multipleOf;
	}
	if ( schema.type === 'integer' ) {
		return 1;
	}
	if ( schema.type === 'number' ) {
		return 'any';
	}
}

// @vue/component
module.exports = exports = defineComponent( {
	name: 'NumberControl',
	components: {
		CdxTextInput,
		ControlWrapper,
	},
	props: Object.assign( {}, rendererProps(), {} ),
	setup( props ) {
		const codexControl = useCodexControl( useJsonFormControl( props ) );

		const schema = codexControl.control.schema;
		const min = ( typeof schema.minimum ) === 'number' ?
			schema.minimum :
			null;
		const max = ( typeof schema.maximum ) === 'number' ?
			schema.maximum :
			null;
		const step = getStepFromSchema( schema );

		const { setValidationErrorForFieldId, clearValidationErrorForFieldId } = useValidationErrors();
		const onInput = ( event ) => {
			const inputElement = event.target;
			if ( inputElement.validationMessage ) {
				setValidationErrorForFieldId( codexControl.controlWrapper.id, inputElement.validationMessage );
			} else {
				clearValidationErrorForFieldId( codexControl.controlWrapper.id );
			}
		};

		return Object.assign( {}, codexControl, {
			onChange( newVal ) {
				if ( newVal === '' ) {
					newVal = undefined;
				}
				codexControl.onChange( newVal );
			},
			onInput,
			min,
			max,
			step,
		} );
	},
} );
</script>
