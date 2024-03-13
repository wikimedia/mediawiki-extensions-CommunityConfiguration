<template>
	<form class="ext-communityConfiguration-JsonForm" @submit="onSubmit">
		<form-layout :schema="schema" :uischema="jsonform.uischema">
		</form-layout>
		<div class="ext-communityConfiguration-JsonForm__submit">
			<slot name="submit"></slot>
		</div>
	</form>
</template>

<script>
const FormLayout = require( './FormLayout.vue' );
const { buildUISchema } = require( '../../core/index.js' );

// @vue/component
module.exports = exports = {
	components: {
		FormLayout
	},
	provide() {
		// Make the main jsonform data available across all components
		return {
			jsonform: this.jsonform
		};
	},
	props: {
		schema: {
			type: Object,
			required: true
		},
		data: {
			type: [ Object, Array ],
			required: true
		},
		renderers: {
			required: true,
			type: Array
		}
	},
	setup: function ( props ) {
		function onSubmit( evt ) {
			evt.preventDefault();
			// TODO: perform real submit or API call
			// eslint-disable-next-line no-console
			console.log( props.data );
		}
		return {
			onSubmit,
			jsonform: {
				data: props.data,
				renderers: props.renderers,
				schema: props.schema,
				uischema: buildUISchema( props.schema )
			}
		};
	}
};
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

.ext-communityConfiguration-JsonForm {
	margin-top: @spacing-50;
	&__submit {
		margin-top: @spacing-50;
	}
}
</style>
