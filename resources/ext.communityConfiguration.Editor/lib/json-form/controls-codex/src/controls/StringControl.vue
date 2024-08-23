<template>
	<control-wrapper v-bind="controlWrapper">
		<cdx-text-input
			v-bind="control.otherAttrs"
			v-model="control.modelValue.value"
			:placeholder="control.uischema.placeholder"
			@update:model-value="trimmedOnChange"
			@input="onInput"
		>
		</cdx-text-input>
	</control-wrapper>
</template>

<script>

const { defineComponent, inject } = require( 'vue' );
const { CdxTextInput } = require( '../../../../../../codex.js' );
const {
	rendererProps,
	useJsonFormControl
} = require( '../../config/index.js' );
const { useCodexControl } = require( '../utils.js' );
const { useValidationErrors } = require( '../../../form/index.js' );
const ControlWrapper = require( './ControlWrapper.vue' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'StringControl',
	components: {
		CdxTextInput,
		ControlWrapper
	},
	props: Object.assign( {}, rendererProps(), {} ),
	setup( props ) {
		const { control, controlWrapper, onChange } = useCodexControl( useJsonFormControl( props ) );
		const { setValidationErrorForFieldId, clearValidationErrorForFieldId } = useValidationErrors();
		const i18n = inject( 'i18n' );
		const onInput = ( event ) => {
			const inputElement = event.target;
			if ( inputElement.validationMessage ) {
				setValidationErrorForFieldId( controlWrapper.id, inputElement.validationMessage );
				return;
			}

			const stringLengthAsItIsCalculatedInJsonrainbow = Array.from( inputElement.value.trim() ).length;
			if ( control.schema.maxLength && stringLengthAsItIsCalculatedInJsonrainbow > control.schema.maxLength ) {
				setValidationErrorForFieldId(
					controlWrapper.id,
					i18n( 'communityconfiguration-editor-error-validation-string-too-long', control.schema.maxLength ).text()
				);
				return;
			}
			if ( control.schema.minLength && stringLengthAsItIsCalculatedInJsonrainbow < control.schema.minLength ) {
				setValidationErrorForFieldId(
					controlWrapper.id,
					i18n( 'communityconfiguration-editor-error-validation-string-too-short', control.schema.minLength ).text()
				);
				return;
			}

			clearValidationErrorForFieldId( controlWrapper.id );
		};

		const trimmedOnChange = ( value ) => {
			onChange( value.trim() );
		};

		return {
			onInput,
			control,
			controlWrapper,
			trimmedOnChange
		};
	}
} );

</script>
