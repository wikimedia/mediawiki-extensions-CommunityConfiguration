<template>
	<div>
		<div v-for="( element, index ) in detailUiSchema.elements" :key="`${element.name}-${index}`">
			<dispatch-renderer :schema="schema" :uischema="element">
			</dispatch-renderer>
		</div>
	</div>
</template>

<script>
const { inject } = require( 'vue' );
const {
	rendererProps,
	DispatchRenderer,
	buildUISchema
} = require( '../../config/index.js' );

// @vue/component
module.exports = exports = {
	name: 'ObjectControl',
	components: {
		DispatchRenderer
	},
	props: Object.assign( {}, rendererProps(), {} ),
	setup( props ) {
		const jsonform = inject( 'jsonform' );
		const detailUiSchema = buildUISchema(
			// eslint-disable-next-line vue/no-undef-properties
			props.schema,
			Object.assign( {}, jsonform.config, {
				// eslint-disable-next-line vue/no-undef-properties
				i18nPrefix: `${jsonform.config.i18nPrefix}-${props.uischema.name}`
			} ),
			props.uischema.scope
		);
		return {
			detailUiSchema
		};
	}
};
</script>
