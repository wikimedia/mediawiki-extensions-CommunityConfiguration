# Use array-based require notation in JsonSchema classes

## Status

Accepted (2024-09-02)

## Context and Problem Statement

CommunityConfiguration uses PHP classes extending the `JsonSchema` class to provide the validation schema for the configuration,
which is also currently used for generating the form.
One aspect of validation is to define a property as required.
This is already possible in sub-properties of objects, but is not yet fully supported for the top-level properties.

The current version of json-schema standard (Draft 4) specifies them as an array:
```json
{
    "type": "object",
    "properties": {
        "foo": {
            "type": "string"
        },
        "bar": {
            "type": "integer"
        }
    },
    "required": ["foo"]
}
```
This works well for sub-properties of objects. However, `ReflectionSchemaSource`, which is used to parse the Schema class
interprets all class constants that have an array as value as properties.
This means that the `required` array is interpreted as a property, which is not what we want.

An alternative approach would be to use a boolean field in the property definition to indicate that a property is required,
which was the previous json-schema standard (Draft 3):
```json
{
    "type": "object",
    "properties": {
        "foo": {
            "type": "string",
            "required": true
        },
        "bar": {
            "type": "integer"
        }
    }
}
```

Draft 3 (published 2010) and Draft 4 (published in 2013) are the implementations that are supported by the currently used library jsonrainbow/json-schema.
However, the most recent version of the json-schema standard is Draft 2020-12, which is 5 major version ahead of Draft 4.
The array-based approach to specify required properties is still the standard in the latest version.
There is a plan to migrate to a library supporting the lastest version of the json-schema standard (opis/json-schema),
but that is waiting on other teams ([T319054](https://phabricator.wikimedia.org/T319054))

## Considered Options

1. Array-based approach ([patch](https://gerrit.wikimedia.org/r/c/mediawiki/extensions/CommunityConfiguration/+/1059149/4)): Each PHP schema will have a certain constant reserved to include the list of required properties. The schema builder would look the constant up and include it in the resulting JSON schema.
1. Boolean-based approach ([patch](https://gerrit.wikimedia.org/r/c/mediawiki/extensions/CommunityConfiguration/+/1063158)): In each property, it would be possible to declare `self::REQUIRED => true` to make that property required. The JSON schema builder would look up that declaration as walking across the properties, and it would build the resulting required array in the JSON schema.

Implementation-wise, both options are similarly complex and can be fully implemented inside CommunityConfiguration.
Both involve adding a little bit of computation into the JSON schema builder.
At the end of the day, both have the same result: JSON schema has a clear definition of how `required` is defined, and we can only follow that definition.
The only room for changes is in our own PHP schemas.
Even though the boolean approach is slightly more expensive than the array-based approach (boolean approach requires a loop, while array approach does not),
both are similarly good with regards to performance, reliability and other aspects.

Visually, the boolean-based approach seems like a slightly better one.
Besides required, all other options that affect a property are defined under that particular property.
For example, if a property `foo` is to be defined as an integer, that is done by declaring `self::TYPE => self::TYPE_INTEGER` directly under that property,
rather than by including the property in a `__TYPE_INTEGER` array (similar to what the array-based approach proposes for required).

However, if we take a look at how `required` already works for properties outside of the top level, they already follow the array-based approach.
There does not seem to be a specific reason, to do the same thing differently in the two places it is needed.


## Decision Outcome

We will use the array-based `required`-notation with a special key on the top-level. The special key will be `__REQUIRED`.
The main considerations are that this is more consistent with how this is handled in sub-properties,
and that it is closer to the current standard in the json-schema specification.

### Consequences

We need to implement it, though for that a change already exists, see above.
We also need to update the documentation for this behavior, probably add an example to make it less confusing
