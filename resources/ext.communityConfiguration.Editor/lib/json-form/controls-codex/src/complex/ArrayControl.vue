<template>
	<!-- REVIEW: maybe apply same pattern than in ObjectControl and only
	wrap the fields in an cdx-field when a label is informed -->
	<cdx-field
		:id="control.pointer"
		:status="control.statusMessages.value.error ? 'error' : 'default'"
		:messages="control.statusMessages.value"
		:is-fieldset="true"
	>
		<div
			v-for="( element, index ) in data"
			:key="`${control.uischema.name}-${index}`"
			class="ext-communityConfiguration-ArrayControl__item-wrapper"
		>
			<dispatch-renderer
				class="ext-communityConfiguration-ArrayControl__item-wrapper__item"
				:schema="control.schema"
				:uischema="indexedChildUISchema( index )"
			></dispatch-renderer>
		</div>
		<template v-if="control.uischema.label && control.uischema.label.exists()" #label>
			{{ control.uischema.label.text() }}
		</template>
	</cdx-field>
</template>

<script>
const { unref } = require( 'vue' );
const { CdxField } = require( '@wikimedia/codex' );
const {
	rendererProps,
	DispatchRenderer,
	useJsonFormArrayControl
} = require( '../../config/index.js' );

// @vue/component
module.exports = exports = {
	name: 'ArrayControl',
	components: {
		CdxField,
		DispatchRenderer
	},
	props: Object.assign( {}, rendererProps(), {} ),
	setup( props ) {
		const arrayControl = useJsonFormArrayControl( props );
		function indexedChildUISchema( index ) {
			return Object.assign( {}, arrayControl.childUISchema, {
				scope: `${arrayControl.childUISchema.scope}/${index}`,
				label: arrayControl.childUISchema.labels[ index ]
			} );
		}
		return Object.assign( {}, arrayControl, {
			indexedChildUISchema,
			data: unref( arrayControl.control.modelValue )
		} );
	}
};
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

.ext-communityConfiguration-ArrayControl__item-wrapper {
	border: @border-subtle;
	padding: @spacing-100;
	margin-bottom: @spacing-50;

	&__item {
		// stylelint-disable-next-line selector-class-pattern
		.cdx-field__control {
			display: flex;
			align-items: baseline;
			justify-content: space-evenly;
			gap: @spacing-100;
			flex-wrap: wrap;

			> * {
				flex: 1;
			}
			// stylelint-disable-next-line selector-class-pattern
			.cdx-lookup {
				width: 100%;
			}
		}
	}
}
</style>
