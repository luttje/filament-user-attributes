<?php

// translations for luttje/filament-user-attributes
return [
    'add_attribute' => 'Attribut hinzufügen',
    'amount' => ':amount Attribute|:amount Attribute',
    'boolean_component_display_no' => 'Nein',
    'boolean_component_display_yes' => 'Ja',
    'common' => 'Allgemein',
    'customizations_for' => 'Anpassungen für :type',
    'default_value_config' => 'Standardwertkonfiguration',
    'inherit_attribute' => 'Attribut zum Erben',
    'inherit_help' => 'Sie können den Standardwert festlegen, indem Sie ihn von einem anderen Attribut erben (auch von einer anderen Ressource).',
    'inherit_relation_option_label' => ':related_name (:resource :relationship :related_resource)',
    'inherit_relation_option_label_self' => ':related_name (selbst)',
    'inherit_relation' => 'Zu erben von',
    'inherit' => 'Wert erben',
    'manage_user_attributes' => 'Attribute verwalten',
    'name_already_exists' => 'Dieser Name existiert bereits, Attribute können nicht denselben Namen haben.',
    'name_help' => 'Dieser Name kann nach der Erstellung nicht mehr geändert werden.',
    'order_sibling_help' => 'Wählen Sie das bestehende Attribut, um dieses davor oder danach zu ordnen.',
    'ordering_form' => 'Im Formular',
    'ordering_table' => 'In Tabelle',
    'ordering' => 'Reihenfolge',
    'select_sibling' => 'Nebenattribut auswählen',
    'suffix_page' => ' Seite',
    'suggestions_help' => 'Durch Kommas getrennte Liste von Vorschlägen für den Benutzer zur Auswahl.',

    /**
     * Short names of relationships.
     */
    'relationships' => [
        '__self' => 'selbst',
        'belongsTo' => 'gehört zu',
        'belongsToMany' => 'gehört zu mehreren',
        'hasMany' => 'hat mehreren',
        'hasManyThrough' => 'hat mehreren',
        'hasOne' => 'hat eins',
        'hasOneThrough' => 'hat eins',
        'morphMany' => 'hat mehreren',
        'morphOne' => 'hat eins',
        'morphTo' => 'gehört zu',
        'morphToMany' => 'gehört zu mehreren',
        'morphedByMany' => 'hat mehreren',
    ],

    /**
     * Names of the attributes that occur in fields.
     */
    'attributes' => [
        'config' => 'Konfiguration',
        'is_searchable' => 'ist durchsuchbar',
        'is_sortable' => 'ist sortierbar',
        'wrap_text' => 'Text umbrechen',
        'is_limited' => 'ist begrenzt',
        'limit' => 'Limit',
        'is_currency' => 'Ist Geld',
        'currency_format' => 'Geldformat',
        'currency_format_common' => 'Gängige Währungen',
        'currency_format_other' => 'Andere Währungen',
        'decimal_places' => 'Dezimalstellen',
        'default' => 'Standard',
        'format' => 'Format',
        'label' => 'Beschriftung',
        'maximum' => 'Maximum',
        'minimum' => 'Minimum',
        'name' => 'Name',
        'order_position_after' => 'nach',
        'order_position_before' => 'vor',
        'order_position_hidden' => 'versteckt',
        'order_position' => 'Position im Verhältnis zum Nebenattribut',
        'order_sibling_at_end' => 'Am Ende',
        'order_sibling' => 'Nebenattribut',
        'placeholder' => 'Platzhalter',
        'required' => 'erforderlich',
        'resource_type' => 'Ressourcentyp',
        'suggestions' => 'Vorschläge',
        'type' => 'Typ',
    ],

    /**
     * Names of types of user attributes.
     */
    'types' => [
        'text' => 'Text (Zeile)',
        'textarea' => 'Text (Absatz)',
        'richeditor' => 'Text (Rich Editor)',
        'number' => 'Zahl',
        'select' => 'Auswahl (Optionen)',
        'checkbox' => 'Kontrollkästchen',
        'toggle' => 'Umschalter',
        'radio' => 'Mehrfachauswahl',
        'tags' => 'Stichwörter',
        'datetime' => 'Datum und Uhrzeit',
        'date' => 'Datum',
        'time' => 'Uhrzeit',
    ],
];
