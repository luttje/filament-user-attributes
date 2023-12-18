<?php

namespace Luttje\FilamentUserAttributes;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

class EloquentHelperRelationshipInfo
{
    public string $name;

    public string $relationTypeShort;

    public string $relationType;

    public string $relatedType;
}

class EloquentHelper
{
    /**
     * Get eloquent relationships by using reflection on the provided model.
     *
     * @see https://github.com/PabloMerener/eloquent-relationships/blob/30ef1c6e4a25de6036dd1003893543744f5a3375/src/helpers.php
     * @return object
     */
    public static function discoverRelations(Model|string $model): array
    {
        // To prevent side effects, we run the methods in a transaction and rollback
        DB::beginTransaction();

        if (is_string($model)) {
            $model = new $model();
        }

        // Get public methods declared without parameters and non inherited
        $instance = $model->replicate();
        $class = get_class($instance);
        $allMethods = (new \ReflectionClass($class))->getMethods(\ReflectionMethod::IS_PUBLIC);
        $methods = array_filter(
            $allMethods,
            function ($method) use ($class) {
                return $method->class === $class && !$method->getParameters(); // relationships have no parameters
            }
        );

        $relations = [];

        foreach ($methods as $method) {
            $methodName = $method->getName();

            try {
                $methodReturn = $instance->$methodName();
            } catch (\Throwable $th) {
                // throw new \Exception("Error while trying to get relation {$methodName} on model {$class}: {$th->getMessage()}");
            }

            if ($methodReturn instanceof Relation) {
                $type = new \ReflectionClass($methodReturn);
                $class = get_class($methodReturn->getRelated());

                $relation = new EloquentHelperRelationshipInfo();
                $relation->name = $methodName;
                $relation->relationTypeShort = lcfirst($type->getShortName()); // belongsTo
                $relation->relationType = $type->getName(); // Illuminate\Database\Eloquent\Relations\BelongsTo
                $relation->relatedType = $class;

                $relations[] = $relation;
            }
        }

        DB::rollBack();

        return $relations;
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
