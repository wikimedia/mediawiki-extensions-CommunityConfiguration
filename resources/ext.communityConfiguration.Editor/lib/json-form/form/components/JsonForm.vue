<template>
	<form class="ext-communityConfiguration-JsonForm" @submit="onSubmit">
		<form-layout :schema="schema" :uischema="jsonform.uischema">
		</form-layout>
		<div class="ext-communityConfiguration-JsonForm__footer-wrapper">
			<div class="ext-communityConfiguration-JsonForm__footer">
				<slot name="submit"></slot>
			</div>
		</div>
	</form>
</template>

<script>
const { reactive } = require( 'vue' );
const FormLayout = require( './FormLayout.vue' );
const { buildUISchema } = require( '../../core/index.js' );

// @vue/component
module.exports = exports = {
	name: 'JsonForm',
	components: {
		FormLayout,
	},
	provide() {
		// Make the main jsonform data available across all components
		return {
			jsonform: this.jsonform,
		};
	},
	props: {
		config: {
			type: Object,
			required: true,
		},
		schema: {
			type: Object,
			required: true,
		},
		data: {
			type: [ Object, Array ],
			required: true,
		},
		renderers: {
			required: true,
			type: Array,
		},
	},
	emits: [ 'submit' ],
	setup( props, { emit } ) {
		// TODO consider using more performant deep clone functions
		const dataClone = JSON.parse( JSON.stringify( props.data ) );
		const data = reactive( dataClone );

		function onSubmit( evt ) {
			evt.preventDefault();
			emit( 'submit', data );
		}

		return {
			onSubmit,
			jsonform: {
				data,
				config: props.config,
				renderers: props.renderers,
				schema: props.schema,
				uischema: buildUISchema( props.schema, props.config, '', dataClone ),
			},
		};
	},
};
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

.ext-communityConfiguration-JsonForm {
	margin-top: @spacing-50;

	&__footer-wrapper {
		position: sticky;
		bottom: 0;
		margin-top: @spacing-100;
		padding-top: @spacing-100;
		background-color: @background-color-base;
	}

	&__footer {
		padding: @spacing-100 @spacing-100 @spacing-100 0;
		border-top: @border-subtle;
	}
	// Cap all Codex fields width
	// stylelint-disable-next-line selector-class-pattern
	.cdx-field {
		max-width: @size-4000;
	}
}
</style>
