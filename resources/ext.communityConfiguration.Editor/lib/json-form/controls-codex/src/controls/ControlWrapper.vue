<template>
	<div :id="name" class="ext-communityConfiguration-ControlWrapper">
		<cdx-field>
			<slot :control-label="controlLabel">
			</slot>
			<template v-if="labelText" #label>
				{{ labelText }}
			</template>
			<template v-if="helpText" #help-text>
				{{ helpText }}
			</template>
		</cdx-field>
	</div>
</template>

<script>
const { inject } = require( 'vue' );
const { CdxField } = require( '@wikimedia/codex' );

// @vue/component
module.exports = exports = {
	name: 'ControlWrapper',
	components: {
		CdxField
	},
	props: {
		name: {
			type: String,
			required: true
		}
	},
	setup: function () {
		const providerName = inject( 'PROVIDER_NAME' );
		const translationKeyPrefix = `communityconfiguration-${providerName}`;
		return {
			translationKeyPrefix
		};
	},
	computed: {
		controlLabel() {
			// Messages that can be used here:
			// * communityconfiguration-<provider_name>-label-<some_field>-control-label
			return this.$i18n(
				`${this.translationKeyPrefix}-${this.name}-control-label`.toLocaleLowerCase()
			).text();
		},
		labelText() {
			// Messages that can be used here:
			// * communityconfiguration-<provider_name>-label-<some_field>
			// * communityconfiguration-<provider_name>-label-<another_field>
			return this.$i18n(
				`${this.translationKeyPrefix}-${this.name}-label`.toLocaleLowerCase()
			).text();
		},
		helpText() {
			// Messages that can be used here:
			// * communityconfiguration-<provider_name>-<some_field>
			// * communityconfiguration-<provider_name>-<another_field>
			const localizationKey = `${this.translationKeyPrefix}-${this.name}-help`;

			return this.$i18n( localizationKey.toLocaleLowerCase() ).text();
		}
	}
};
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

.ext-communityConfiguration-ControlWrapper {
	margin-top: @spacing-50;
}
</style>
