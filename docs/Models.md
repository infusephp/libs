Models
=====

## Properties

The properties for each model are defined using the `$properties` variable on every model class.

### Schema Options

#### `type`

The type of the property.

Accepted Types:
- string
- number
- boolean
- date
- array
- object

String, Required

#### `default`

The default value to be used when creating new models.

String, Optional

### Validation Options

#### `mutable`
        Specifies whether the property can be set (mutated)
        Boolean
        Default: true
        Optional

#### `validate`

Validation string passed to Validate::is() or validation function
        String or callable
        Optional

#### `required`

Specifies whether the field is required
        Boolean
        Default: false
        Optional

#### `unique`

Specifies whether the field is required to be unique
        Boolean
        Default: false
        Optional

#### `null`

Specifies whether the column is allowed to have null values

Boolean, Optional, Default: `false`

### Query Options

#### `searchable`

Specifies whether the property should be searched when querying models

Boolean, Optional, Default: `false`

### Meta Options

#### `title`

Title of the property that shows up in admin panel

String, Optional, Default: Derived from property name

#### `relation`

Model class name (including namespace) the property is related to

String, Optional

#### `hidden`

Hides a property when expanding the model, i.e. toArray()

Boolean, Optional, Default: false