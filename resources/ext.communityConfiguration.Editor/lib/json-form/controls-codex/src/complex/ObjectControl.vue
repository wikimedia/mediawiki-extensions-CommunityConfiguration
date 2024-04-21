<template>
	<div>
		<cdx-field
			v-if="control.uischema.label && control.uischema.label.exists()"
			:is-fieldset="true"
		>
			<div v-for="( element, index ) in detailUiSchema.elements" :key="`${element.name}-${index}`">
				<dispatch-renderer :schema="schema" :uischema="element">
				</dispatch-renderer>
			</div>
			<template #label>
				{{ control.uischema.label.text() }}
			</template>
		</cdx-field>
		<div
			v-for="( element, index ) in detailUiSchema.elements"
			v-else
			:key="`${element.name}-${index}`"
		>
			<dispatch-renderer :schema="schema" :uischema="element">
			</dispatch-renderer>
		</div>
	</div>
</template>

<script>
const { inject, unref } = require( 'vue' );
const { CdxField } = require( '@wikimedia/codex' );
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
			detailUiSchema
		};
	}
};
</script>
