<?php

namespace Luttje\FilamentUserAttributes;

class EloquentHelperRelationshipInfo
{
    public string $name;

    public string $relationTypeShort;

    public string $relationType;

    public string $relatedType;

    /**
     * The column on this model that holds the key of the related model (only
     * for relations like BelongsTo), e.g. 'user_id' for an `author()` relation.
     */
    public ?string $foreignKeyName = null;

    /**
     * The names a form field for this relation may have, from most to least
     * specific: the foreign key (e.g. 'user_id'), the relation name, and the
     * relation name followed by '_id'.
     *
     * @return string[]
     */
    public function getFormFieldNames(): array
    {
        return array_values(array_unique(array_filter([
            $this->foreignKeyName,
            $this->name,
            $this->name . '_id',
        ])));
    }
}
