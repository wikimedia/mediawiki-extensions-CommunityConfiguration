/**
 * Public interface of the ext.communityConfiguration.Editor.controls module.
 *
 * This module holds the parts of json-form that a form control needs. Both the controls of
 * CommunityConfiguration and the controls of other extensions use it. Nothing in this module
 * requires codex.js, because ResourceLoader gives each Codex module its own copy of Codex.
 * A control must therefore get its Codex components from its own module.
 */
const {
	buildUISchema,
	buildUISubSchema,
	rankRenderer,
	rankWith,
	schemaControlIs,
	isArrayControl,
	isBooleanControl,
	isIntegerControl,
	isNumberControl,
	isStringControl,
	isEnumControl,
	isSimpleStringArrayControl,
	isMultiSelectEnumControl,
	isObjectControl,
} = require( './core/index.js' );
const {
	rendererProps,
	useJsonFormArrayControl,
	useJsonFormControl,
	useJsonFormRenderer,
	useValidationErrors,
	DispatchRenderer,
	JsonForm,
} = require( './form/index.js' );
const {
	debounce,
	useCodexControl,
} = require( './controls-codex/src/utils.js' );

module.exports = exports = {
	buildUISchema,
	buildUISubSchema,
	debounce,
	DispatchRenderer,
	isArrayControl,
	isBooleanControl,
	isIntegerControl,
	isStringControl,
	isEnumControl,
	isSimpleStringArrayControl,
	isMultiSelectEnumControl,
	isNumberControl,
	isObjectControl,
	JsonForm,
	rankRenderer,
	rankWith,
	schemaControlIs,
	rendererProps,
	useCodexControl,
	useJsonFormArrayControl,
	useJsonFormControl,
	useJsonFormRenderer,
	useValidationErrors,
};
