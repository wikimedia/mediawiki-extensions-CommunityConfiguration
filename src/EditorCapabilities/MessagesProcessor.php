<?php

namespace MediaWiki\Extension\CommunityConfiguration\EditorCapabilities;

use Error;
use Iterator;
use MediaWiki\Extension\CommunityConfiguration\Schema\JsonSchema;
use MediaWiki\Extension\CommunityConfiguration\Schema\JsonSchemaIterator;
use MediaWiki\Extension\CommunityConfiguration\Schema\UISchema;
use MediaWiki\Language\MessageLocalizer;
use Psr\Log\LoggerInterface;
use RuntimeException;

class MessagesProcessor {

	public function __construct(
		private readonly LoggerInterface $logger,
		private readonly MessageLocalizer $messageLocalizer
	) {
	}

	/**
	 * @param string $providerId
	 * @param Iterator $schema
	 * @param string $messagePrefix
	 * @return array
	 */
	public function getMessages( string $providerId, Iterator $schema, string $messagePrefix ): array {
		if ( !$schema instanceof JsonSchemaIterator ) {
			$this->logger->debug( __CLASS__ . ' skipped schema Iterator, because it is not a JsonSchemaIterator.' );
			return [];
		}

		$messages = [];
		$keys = $this->computeEditorMessageKeys( $providerId, $schema, $messagePrefix );
		// Present for any schema a used in the editor summary dialog
		array_unshift( $keys, strtolower( $messagePrefix . '-' . $providerId . '-title' ) );
		foreach ( $keys as $key ) {
			$msg = $this->messageLocalizer->msg( $key );
			// TODO: consider adding logging for non-existent messages (T373398)
			if ( $msg->exists() ) {
				$messages[$key] = $msg->plain();
			}
		}

		return $messages;
	}

	/**
	 * Resolve the messages that a UI schema needs.
	 *
	 * getMessages() walks the data schema, so it does not cover the messages that live in the
	 * UI schema. Those are the heading of a Group, and the extra keys that a control asks for
	 * with the MESSAGES key.
	 *
	 * The heading keys follow the same convention as the control messages:
	 * `<prefix>-<providerId>-<label>-section-label`, and the same with `-section-description`.
	 *
	 * @param string $providerId
	 * @param array $uiSchema A layout, shaped as `[ 'elements' => [ ... ] ]`
	 * @param string $messagePrefix
	 * @return array Map of message key to plain text, for messages that exist
	 */
	public function getUiSchemaMessages(
		string $providerId, array $uiSchema, string $messagePrefix
	): array {
		$keys = [];
		foreach ( UISchema::flattenElements( $uiSchema ) as $element ) {
			$label = $element[UISchema::LABEL] ?? null;
			if ( ( $element[UISchema::TYPE] ?? null ) === UISchema::TYPE_GROUP && $label !== null ) {
				$baseKey = strtolower( $messagePrefix . '-' . $providerId . '-' . $label . '-section' );
				$keys[] = $baseKey . '-label';
				$keys[] = $baseKey . '-description';
			}
			foreach ( $element[UISchema::MESSAGES] ?? [] as $key ) {
				if ( is_string( $key ) ) {
					$keys[] = $key;
				}
			}
		}

		$messages = [];
		foreach ( array_unique( $keys ) as $key ) {
			$msg = $this->messageLocalizer->msg( $key );
			if ( $msg->exists() ) {
				$messages[$key] = $msg->plain();
			} else {
				// A control that asks for a message it does not have is a typo that is
				// otherwise invisible, because the client falls back to the key.
				$this->logger->info(
					'CommunityConfiguration: the UI schema of {provider} asks for the message ' .
						'{key}, which does not exist.',
					[ 'provider' => $providerId, 'key' => $key ]
				);
			}
		}
		return $messages;
	}

	/**
	 * @param string $jsonPointer
	 * @param string $prefix
	 * @return string
	 */
	private function mapJsonPointerToMessageKey( string $jsonPointer, string $prefix = '' ): string {
		// Remove initial #
		$jsonPointer = str_replace( '#', '', $jsonPointer );
		// Remove /properties and /items occurrences since we omit them in the message key
		$messageKey = str_replace( [ '/properties', '/items' ], '', $jsonPointer );
		// Replace slashes by hyphens to follow message key convention
		$messageKey = str_replace( [ '/' ], '-', $messageKey );
		return strtolower( $prefix . $messageKey );
	}

	/**
	 * Returns the value of a given field in the given class.
	 *
	 * @param string $className
	 * @param string $fieldName
	 * @return array The value of the class field
	 */
	private function getControlMessages( string $className, string $fieldName ): array {
		try {
			$value = constant( "$className::$fieldName" );
		} catch ( Error ) {
			throw new RuntimeException(
				"Failed resolving reference $fieldName in $className."
			);
		}
		return $value;
	}

	/**
	 * @param string $providerId
	 * @param JsonSchemaIterator $schemas
	 * @param string $messagePrefix
	 * @return array
	 */
	private function computeEditorMessageKeys(
		string $providerId, JsonSchemaIterator $schemas, string $messagePrefix
	): array {
		$messages = [];
		foreach ( $schemas as $sub_schema ) {
			[
				'parentType' => $parentType,
				'pointer' => $pointer,
				'schema' => $schema,
			] = $sub_schema;

			if ( $pointer === '#' ) {
				// We don't generate labels for the root object
				continue;
			}

			$schemaBaseKey = $this->mapJsonPointerToMessageKey( $pointer, $messagePrefix . '-' . $providerId );
			if ( isset( $schema->{JsonSchema::CONTROL} ) ) {
				$messages = array_merge( $messages, $this->getControlMessages(
					$schema->{JsonSchema::CONTROL}, 'MESSAGES'
				) );
			}
			if ( isset( $schema->{JsonSchema::ENUM} ) ) {
				$messages[] = $schemaBaseKey . '-label';
				$messages[] = $schemaBaseKey . '-help-text';
				$messages[] = $schemaBaseKey . '-description';
				if ( $parentType === JsonSchema::TYPE_ARRAY ) {
					if ( $schema->{JsonSchema::TYPE} === JsonSchema::TYPE_STRING ) {
						$messages[] = 'mw-widgets-titlesmultiselect-placeholder';
						$messages[] = 'communityconfiguration-editor-chip-control-aria-chip-description';
						$messages[] = 'communityconfiguration-editor-error-validation-array-items-max';
						$messages[] = 'communityconfiguration-editor-error-validation-array-items-min';
						$messages[] = 'communityconfiguration-editor-error-validation-enum-invalid-value';
					}
				}
				foreach ( $schema->{JsonSchema::ENUM} as $enumValue ) {
					$messages[] = $schemaBaseKey . '-option-' . strtolower( $enumValue ) . '-label';
				}
				// Do not process the schema type for enums as it would generate
				// undesired keys, eg: placeholders
				continue;
			}
			if (
				$schema->{JsonSchema::TYPE} === JsonSchema::TYPE_STRING ||
				$schema->{JsonSchema::TYPE} === JsonSchema::TYPE_NUMBER ||
				$schema->{JsonSchema::TYPE} === JsonSchema::TYPE_INTEGER
			) {
				if ( $parentType === JsonSchema::TYPE_ARRAY ) {
					if ( $schema->{JsonSchema::TYPE} === JsonSchema::TYPE_STRING ) {
						$messages[] = 'mw-widgets-titlesmultiselect-placeholder';
						$messages[] = 'communityconfiguration-editor-chip-control-aria-chip-description';
					}
					// We don't generate labels for arrays of simple types
					// TODO: consider adding support for min/max length client validation errors for arrays of strings
					continue;
				}
				if (
					!isset( $schema->{JsonSchema::CONTROL} ) &&
					$schema->{JsonSchema::TYPE} === JsonSchema::TYPE_STRING
				) {
					$messages[] = 'communityconfiguration-editor-error-validation-string-too-short';
					$messages[] = 'communityconfiguration-editor-error-validation-string-too-long';
				}
				$messages[] = $schemaBaseKey . '-label';
				$messages[] = $schemaBaseKey . '-help-text';
				$messages[] = $schemaBaseKey . '-placeholder';
				$messages[] = $schemaBaseKey . '-description';
			}
			if ( $schema->{JsonSchema::TYPE} === JsonSchema::TYPE_BOOLEAN ) {
				$messages[] = $schemaBaseKey . '-label';
				$messages[] = $schemaBaseKey . '-help-text';
				$messages[] = $schemaBaseKey . '-control-label';
				$messages[] = $schemaBaseKey . '-description';
			}
			if (
				$schema->{JsonSchema::TYPE} === JsonSchema::TYPE_OBJECT ||
				$schema->{JsonSchema::TYPE} === JsonSchema::TYPE_ARRAY
			) {
				$messages[] = $schemaBaseKey . '-label';
				$messages[] = $schemaBaseKey . '-help-text';
				$messages[] = $schemaBaseKey . '-description';
				if (
					$schema->{JsonSchema::TYPE} === JsonSchema::TYPE_ARRAY &&
					// Arrays of strings are rendered on a single field with chips
					$schema->{JsonSchema::ITEMS}->{JsonSchema::TYPE} !== JsonSchema::TYPE_STRING &&
					// Assume array types with a custom control will handle its own item labels
					!isset( $schema->{JsonSchema::CONTROL} )
				) {
					$messages[] = $schemaBaseKey . '-item-label';
					$messages[] = $schemaBaseKey . '-add-element-button-label';
					$messages[] = 'communityconfiguration-editor-array-fallback-add-element-button-label';
				}
			}
		}
		return $messages;
	}
}
