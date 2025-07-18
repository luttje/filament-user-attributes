<?php

// translations for luttje/filament-user-attributes
return [
    'add_attribute' => 'Voeg aangepast veld toe',
    'amount' => ':amount aangepast veld|:amount aangepaste velden',
    'boolean_component_display_no' => 'Nee',
    'boolean_component_display_yes' => 'Ja',
    'common' => 'Algemeen',
    'customizations_for' => 'Aanpassingen voor :type',
    'default_value_config' => 'Standaardwaarde configuratie',
    'inherit_attribute' => 'Veld om van te erven',
    'inherit_help' => 'U kunt de standaardwaarde instellen door deze te erven van een ander veld (ook van een andere bron).',
    'inherit_relation_option_label' => ':related_name (:resource :relationship :related_resource)',
    'inherit_relation_option_label_self' => ':related_name (zelf)',
    'inherit_relation' => 'Te erven van',
    'inherit' => 'Waarde erven',
    'manage_user_attributes' => 'Beheer aangepaste velden',
    'name_already_exists' => 'Deze naam bestaat al, aangepaste velden kunnen niet dezelfde naam hebben.',
    'name_help' => 'Deze naam kan niet meer worden gewijzigd na het aanmaken.',
    'order_sibling_help' => 'Kies het bestaande veld om dit veld ervoor of erna te ordenen.',
    'ordering_form' => 'In Formulier',
    'ordering_table' => 'In Tabel',
    'ordering' => 'Volgordelijkheid',
    'select_sibling' => 'Selecteer naastgelegen veld',
    'suffix_page' => ' Pagina',
    'suggestions_help' => "Door komma's gescheiden lijst van suggesties voor de gebruiker om uit te kiezen.",

    /**
     * Short names of relationships.
     */
    'relationships' => [
        '__self' => 'zelf',
        'belongsTo' => 'behoort tot',
        'belongsToMany' => 'behoort tot meerdere',
        'hasMany' => 'heeft meerdere',
        'hasManyThrough' => 'heeft meerdere',
        'hasOne' => 'heeft een',
        'hasOneThrough' => 'heeft een',
        'morphMany' => 'heeft meerdere',
        'morphOne' => 'heeft een',
        'morphTo' => 'behoort naar',
        'morphToMany' => 'behoort naar meerdere',
        'morphedByMany' => 'heeft meerdere',
    ],

    /**
     * Names of the attributes that occur in fields.
     */
    'attributes' => [
        'config' => 'configuratie',
        'is_searchable' => 'is doorzoekbaar',
        'is_sortable' => 'is sorteerbaar',
        'wrap_text' => 'tekst teruglopen',
        'is_limited' => 'is beperkt',
        'limit' => 'limiet',
        'is_currency' => 'is geld',
        'currency_format' => 'geld formaat',
        'currency_format_common' => 'Veelvoorkomende valuta',
        'currency_format_other' => 'Andere valuta',
        'decimal_places' => 'decimale plaatsen',
        'default' => 'standaard',
        'format' => 'formaat',
        'label' => 'label',
        'maximum' => 'maximum',
        'minimum' => 'minimum',
        'name' => 'naam',
        'order_position_after' => 'na',
        'order_position_before' => 'voor',
        'order_position_hidden' => 'verborgen',
        'order_position' => 'positie t.o.v. naastgelegen veld',
        'order_sibling_at_end' => 'Aan het einde',
        'order_sibling' => 'naastgelegen veld',
        'placeholder' => 'placeholder',
        'required' => 'vereist',
        'resource_type' => 'bron type',
        'suggestions' => 'suggesties',
        'type' => 'type',
    ],

    /**
     * Names of types of user attributes.
     */
    'types' => [
        'text' => 'Tekst (Regel)',
        'textarea' => 'Tekst (Paragraaf)',
        'richeditor' => 'Tekst (Uitgebreide Editor)',
        'number' => 'Nummer',
        'select' => 'Selecteren (Opties)',
        'checkbox' => 'Vinkdoos',
        'toggle' => 'Schakelaar',
        'radio' => 'Meerdere keuzes',
        'tags' => 'Tags',
        'datetime' => 'Datum en tijd',
        'date' => 'Datum',
        'time' => 'Tijd',
    ],
];
