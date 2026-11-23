<?php

namespace Luttje\FilamentUserAttributes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Relation;

class EloquentHelper
{
    /**
     * Get eloquent relationships by using reflection on the provided model.
     *
     * Only methods that declare a relation as their return type are considered
     * relationships, for example:
     *
     *     public function customer(): BelongsTo
     *
     * Methods without such a return type are never called, because calling
     * arbitrary model methods to see what they return can have side effects.
     */
    public static function discoverRelations(Model|string $model): array
    {
        if (is_string($model)) {
            $model = new $model();
        }

        $instance = $model->replicate();
        $class = get_class($instance);
        $allMethods = (new \ReflectionClass($class))->getMethods(\ReflectionMethod::IS_PUBLIC);
        $methods = array_filter(
            $allMethods,
            function (\ReflectionMethod $method) use ($class) {
                return $method->class === $class // non inherited
                    && static::isRelationMethod($method);
            }
        );

        $relations = [];

        foreach ($methods as $method) {
            $methodName = $method->getName();

            try {
                $methodReturn = $instance->$methodName();
            } catch (\Throwable $th) {
                $methodReturn = null;
            }

            if ($methodReturn instanceof Relation) {
                $type = new \ReflectionClass($methodReturn);
                $relatedClass = get_class($methodReturn->getRelated());

                $relation = new EloquentHelperRelationshipInfo();
                $relation->name = $methodName;
                $relation->relationTypeShort = lcfirst($type->getShortName()); // belongsTo
                $relation->relationType = $type->getName(); // Illuminate\Database\Eloquent\Relations\BelongsTo
                $relation->relatedType = $relatedClass;

                if ($methodReturn instanceof BelongsTo) {
                    $relation->foreignKeyName = $methodReturn->getForeignKeyName();
                }

                $relations[] = $relation;
            }
        }

        return $relations;
    }

    /**
     * Whether the method is declared like a relationship: an instance method
     * without parameters that has a relation as its return type.
     */
    protected static function isRelationMethod(\ReflectionMethod $method): bool
    {
        if ($method->isStatic() || $method->isAbstract() || $method->getNumberOfParameters() > 0) {
            return false;
        }

        $returnType = $method->getReturnType();

        if (!$returnType instanceof \ReflectionNamedType || $returnType->isBuiltin()) {
            return false;
        }

        return is_a($returnType->getName(), Relation::class, true);
    }

    /**
     * Whether the model hides the attribute (through `$hidden` or `$visible`).
     *
     * Hidden attributes, like a password, should never be exposed to users,
     * so they can't be used as the source for an inherited user attribute.
     */
    public static function isHiddenAttribute(Model|string $model, string $attributePath): bool
    {
        if (is_string($model)) {
            $model = new $model();
        }

        $attribute = explode('.', $attributePath)[0];
        $visible = $model->getVisible();

        if (in_array($attribute, $model->getHidden(), true)) {
            return true;
        }

        return !empty($visible) && !in_array($attribute, $visible, true);
    }

    /**
     * Gets the relation type for the given relation name.
     */
    public static function getRelationInfo(Model|string $model, string $relationName): ?EloquentHelperRelationshipInfo
    {
        $relations = self::discoverRelations($model);

        foreach ($relations as $relation) {
            if ($relation->name === $relationName) {
                return $relation;
            }
        }

        return null;
    }
}
