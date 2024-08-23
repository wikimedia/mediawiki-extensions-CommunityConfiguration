<template>
	<cdx-field
		v-if="control.uischema.label && control.uischema.label.exists()"
		:id="control.pointer"
		:is-fieldset="true"
	>
		<dispatch-renderer
			v-for="( element, index ) in detailUiSchema.elements"
			:key="`${element.name}-${index}`"
			:schema="schema"
			:uischema="element"
		></dispatch-renderer>
		<template #label>
			{{ control.uischema.label.text() }}
		</template>
		<template v-if="description && description.exists()" #description>
			<span v-i18n-html="description"></span>
		</template>
		<template v-if="helpText && helpText.exists()" #help-text>
			<span v-i18n-html="helpText"></span>
		</template>
	</cdx-field>
	<dispatch-renderer
		v-for="( element, index ) in detailUiSchema.elements"
		v-else
		:key="`${element.name}-${index}`"
		:schema="schema"
		:uischema="element"
	></dispatch-renderer>
</template>

<script>
const { inject, unref } = require( 'vue' );
const { CdxField } = require( '../../../../../../codex.js' );
const {
	rendererProps,
	DispatchRenderer,
	buildUISchema,
	useJsonFormControl
} = require( '../../config/index.js' );

// @vue/component
module.exports = exports = {
	name: 'ObjectControl',
	components: {
		CdxField,
		DispatchRenderer
	},
	props: Object.assign( {}, rendererProps(), {} ),
	setup( props ) {
		const jsonform = inject( 'jsonform' );
		const { control } = useJsonFormControl( props );
		const detailUiSchema = buildUISchema(
			// eslint-disable-next-line vue/no-undef-properties
			props.schema,
			Object.assign( {}, jsonform.config, {
				// eslint-disable-next-line vue/no-undef-properties
				i18nPrefix: `${jsonform.config.i18nPrefix}-${props.uischema.name}`
			} ),
			props.uischema.scope,
			unref( control.modelValue )
		);
		return {
			control,
			description: control.uischema.description,
			helpText: control.uischema.helpText,
			detailUiSchema
		};
	}
};
</script>
