<template>
	<div class="ext-communityConfiguration-App">
		<json-form
			:data="configData"
			:schema="schema"
			:renderers="renderers"
		>
			<template #submit>
				<cdx-button action="progressive" weight="primary">
					Submit
				</cdx-button>
			</template>
		</json-form>
	</div>
</template>

<script>
const { inject } = require( 'vue' );
const { CdxButton } = require( '@wikimedia/codex' );
const { JsonForm } = require( './lib/json-form/form/index.js' );
const { renderers } = require( './lib/json-form/controls-codex/src/index.js' );

// @vue/component
module.exports = exports = {
	name: 'App',
	components: {
		JsonForm,
		CdxButton
	},
	setup: function () {
		const configData = inject( 'CONFIG_DATA' );
		const schema = inject( 'JSON_SCHEMA' );

		return {
			configData,
			renderers,
			schema
		};
	}
};
</script>
