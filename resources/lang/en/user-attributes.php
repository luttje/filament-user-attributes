<?php

// translations for luttje/filament-user-attributes
return [
    'add_attribute' => 'Add attribute',
    'amount' => ':amount attribute|:amount attributes',
    'boolean_component_display_no' => 'No',
    'boolean_component_display_yes' => 'Yes',
    'common' => 'Common',
    'customizations_for' => 'Customizations for :type',
    'default_value_config' => 'Default value configuration',
    'inherit_attribute' => 'Attribute to inherit from',
    'inherit_help' => 'You can set the default value by inheriting it from another attribute (even from another resource).',
    'inherit_relation_option_label' => ':related_name (:resource :relationship :related_resource)',
    'inherit_relation_option_label_self' => ':related_name (self)',
    'inherit_relation' => 'Relation to inherit from',
    'inherit' => 'Inherit value',
    'manage_user_attributes' => 'Manage user attributes',
    'name_already_exists' => 'This name already exists, attributes cannot have the same name.',
    'name_help' => 'This name can not be changed after creation.',
    'order_sibling_help' => 'Choose the existing attribute to order this one before or after.',
    'ordering_form' => 'In Form',
    'ordering_table' => 'In Table',
    'ordering' => 'Ordering',
    'select_sibling' => 'Select sibling',
    'suffix_page' => ' Page',
    'suggestions_help' => 'Comma separated list of suggestions for the user to choose from.',

    /**
     * Short names of relationships.
     */
    'relationships' => [
        '__self' => 'self',
        'belongsTo' => 'belongs to',
        'belongsToMany' => 'belongs to multiple',
        'hasMany' => 'has multiple',
        'hasManyThrough' => 'has multiple',
        'hasOne' => 'has one',
        'hasOneThrough' => 'has one',
        'morphMany' => 'has multiple',
        'morphOne' => 'has one',
        'morphTo' => 'belongs to',
        'morphToMany' => 'belongs to multiple',
        'morphedByMany' => 'has multiple',
    ],

    /**
     * Names of the attributes that occur in fields.
     */
    'attributes' => [
        'config' => 'config',
        'wrap_text' => 'wrap text',
        'is_limited' => 'is limited',
        'limit' => 'limit',
        'is_currency' => 'is currency',
        'currency_format' => 'currency format',
        'currency_format_common' => 'Common currencies',
        'currency_format_other' => 'Other currencies',
        'decimal_places' => 'decimal places',
        'default' => 'default',
        'format' => 'format',
        'label' => 'label',
        'maximum' => 'maximum',
        'minimum' => 'minimum',
        'name' => 'name',
        'order_position_after' => 'after',
        'order_position_before' => 'before',
        'order_position_hidden' => 'hidden',
        'order_position' => 'order position',
        'order_sibling_at_end' => 'At the end',
        'order_sibling' => 'order sibling',
        'placeholder' => 'placeholder',
        'required' => 'required',
        'resource_type' => 'resource type',
        'suggestions' => 'suggestions',
        'type' => 'type',
    ],
];
