'use strict';
/*
 * For a detailed explanation regarding each configuration property, visit:
 * https://jestjs.io/docs/configuration
 */

module.exports = {
	// Vue-jest specific global options (described here: https://github.com/vuejs/vue-jest#global-jest-options)
	globals: {
		babelConfig: false,
		hideStyleWarn: true,
		experimentalCssCompile: true,
	},
	// This and "transform" below are the most crucial for vue-jest:
	// https://github.com/vuejs/vue-jest#setup
	moduleFileExtensions: [
		'js',
		'json',
		'vue',
	],
	transform: {
		'.*\\.(vue)$': '<rootDir>/node_modules/@vue/vue3-jest',
	},
	testEnvironment: 'jsdom',
	testEnvironmentOptions: {
		customExportConditions: [ 'node', 'node-addons' ],
	},
	// A map from regular expressions to module names or to arrays of module
	// names that allow to stub out resources with a single module
	moduleNameMapper: {
		'icons.json': '@wikimedia/codex-icons',
		'codex.js': '@wikimedia/codex',
	},
	// Indicates whether the coverage information should be collected while executing the test
	collectCoverage: true,
	collectCoverageFrom: [
		'resources/ext.communityConfiguration.Editor/**/*.(js|vue)',
	],
	// The directory where Jest should output its coverage files
	coverageDirectory: 'coverage',
	// Thresholds specified as a positive number are taken to be the minimum percentage required.
	// Thresholds specified as a negative number represent the maximum number of uncovered
	// entities allowed.
	coverageThreshold: {
		global: {
			branches: 5,
			functions: 12,
			lines: 42,
		},
	},
	// A list of paths to directories that Jest should use to search for files in.
	roots: [
		'./resources/ext.communityConfiguration.Editor',
		'./resources/ext.communityConfiguration.Editor.common',
	],
	setupFiles: [
		'./jest.setup.js',
	],
	setupFilesAfterEnv: [
		'./jest.setupAfterEnv.js',
	],
};
