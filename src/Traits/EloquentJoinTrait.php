<?php

namespace Fico7489\Laravel\EloquentJoin\Traits;

use Fico7489\Laravel\EloquentJoin\Relations\BelongsToJoin;
use Fico7489\Laravel\EloquentJoin\Exceptions\EloquentJoinException;
use Fico7489\Laravel\EloquentJoin\Relations\BelongsToManyJoin;
use Fico7489\Laravel\EloquentJoin\Relations\HasOneJoin;
use Fico7489\Laravel\EloquentJoin\Services\QueryNormalizer;
use Illuminate\Database\Eloquent\Builder;

trait EloquentJoinTrait
{
    use ExtendRelationsTrait;

    //use table alias for join (real table name or uniqid())
    protected $useTableAlias = false;

    //store if ->select(...) is already called on builder (we want only one select)
    private $selected = false;

    //store joined tables, we want join table only once (e.g. when you call orderByJoin more time)
    private $joinedTables = [];

    //store joined pivots aliases, this way we don't have to worry about multiple pivots with the same name
    private $joinedPivots = [];


    //store not allowed clauses on join relations for throw exception (e.g. whereHas, orderBy etc.)
    public $relationNotAllowedClauses = [];

    //store where clauses which we will use for join
    public $relationWhereClauses = [];

    //store soft delete clauses (withoutTrashed|onlyTrashed|WithTrashed)
    public $softDelete = 'withoutTrashed';


    //set invalid clauses on join relations
    public function scopeSetInvalidJoin(Builder $builder, $method, $parameters = [])
    {
        $this->relationNotAllowedClauses[$method] = $method;
    }

    //set where clause for join relations
    public function scopeSetWhereForJoin(Builder $builder, $column, $operator = null, $value = null, $boolean = 'and')
    {
        $this->relationWhereClauses[] = ['column' => $column, 'operator' => $operator, 'value' => $value, 'boolean' => $boolean];
    }

    //set orWhere clause for join relations
    public function scopeSetOrWhereForJoin(Builder $builder, $column, $operator = null, $value)
    {
        $this->relationWhereClauses[] = ['column' => $column, 'operator' => $operator, 'value' => $value, 'boolean' => 'or'];
    }

    //set soft delete clauses for join relations
    public function scopeSetSoftDelete(Builder $builder, $param)
    {
        $this->softDelete = $param;
    }


    public function scopeWhereJoin(Builder $builder, $column, $operator = null, $value = null, $boolean = 'and')
    {
        list($column, $operator, $value) = QueryNormalizer::normalizeScope(func_get_args());
        $column = $this->performJoin($builder, $column, 'inner');

        return $builder->where($column, $operator, $value, $boolean);
    }
    public function scopeWhereLeftJoin(Builder $builder, $column, $operator = null, $value = null, $boolean = 'and')
    {
        list($column, $operator, $value) = QueryNormalizer::normalizeScope(func_get_args());
        $column = $this->performJoin($builder, $column, 'left');

        return $builder->where($column, $operator, $value, $boolean);
    }
    public function scopeWhereCrossJoin(Builder $builder, $column, $operator = null, $value = null, $boolean = 'and')
    {
        list($column, $operator, $value) = QueryNormalizer::normalizeScope(func_get_args());
        $column = $this->performJoin($builder, $column, 'cross');

        return $builder->where($column, $operator, $value, $boolean);
    }

    public function scopeOrWhereJoin(Builder $builder, $column, $operator = null, $value)
    {
        list($column, $operator, $value) = QueryNormalizer::normalizeScope(func_get_args());
        $column = $this->performJoin($builder, $column, 'inner');

        return $builder->orWhere($column, $operator, $value);
    }
    public function scopeOrWhereLeftJoin(Builder $builder, $column, $operator = null, $value)
    {
        list($column, $operator, $value) = QueryNormalizer::normalizeScope(func_get_args());
        $column = $this->performJoin($builder, $column, 'left');

        return $builder->orWhere($column, $operator, $value);
    }
    public function scopeOrWhereCrossJoin(Builder $builder, $column, $operator = null, $value)
    {
        list($column, $operator, $value) = QueryNormalizer::normalizeScope(func_get_args());
        $column = $this->performJoin($builder, $column, 'cross');

        return $builder->orWhere($column, $operator, $value);
    }

    public function scopeOrderByJoin(Builder $builder, $column, $sortBy = 'asc')
    {
        $column = $this->performJoin($builder, $column, 'inner');
        return $builder->orderBy($column, $sortBy);
    }
    public function scopeOrderByLeftJoin(Builder $builder, $column, $sortBy = 'asc')
    {
        $column = $this->performJoin($builder, $column, 'left');
        return $builder->orderBy($column, $sortBy);
    }
    public function scopeOrderByCrossJoin(Builder $builder, $column, $sortBy = 'asc')
    {
        $column = $this->performJoin($builder, $column, 'cross');
        return $builder->orderBy($column, $sortBy);
    }

    public function scopeOrderByJoinPivot(Builder $builder, $column, $sortBy = 'asc')
    {
        $qualifiedColumn = $this->performJoin($builder, $column, 'inner');
        
        $relations = explode('.', $column);
        $relationName = current($relations);
        $column = end($relations);
        $relation = $this->$relationName();

        if (!$relation instanceof BelongsToManyJoin) {
            throw new EloquentJoinException('Only allowed relation for orderByJoinPivot is BelongsToManyJoin');
        }

        $builder->orderBy($this->joinedPivots[$relationName] . '.' . $column, $sortBy);
        
        return $builder;
    }
    public function scopeOrderByLeftJoinPivot(Builder $builder, $column, $sortBy = 'asc')
    {
        $qualifiedColumn = $this->performJoin($builder, $column, 'left');
        
        $relations = explode('.', $column);
        $relationName = current($relations);
        $column = end($relations);
        $relation = $this->$relationName();

        if (!$relation instanceof BelongsToManyJoin) {
            throw new EloquentJoinException('Only allowed relation for orderByJoinPivot is BelongsToManyJoin');
        }

        $builder->orderBy($this->joinedPivots[$relationName] . '.' . $column, $sortBy);
        
        return $builder;
    }
    public function scopeOrderByCrossJoinPivot(Builder $builder, $column, $sortBy = 'asc')
    {
        $qualifiedColumn = $this->performJoin($builder, $column, 'cross');
        
        $relations = explode('.', $column);
        $relationName = current($relations);
        $column = end($relations);
        $relation = $this->$relationName();

        if (!$relation instanceof BelongsToManyJoin) {
            throw new EloquentJoinException('Only allowed relation for orderByJoinPivot is BelongsToManyJoin');
        }

        $builder->orderBy($this->joinedPivots[$relationName] . '.' . $column, $sortBy);
        
        return $builder;
    }

    private function performJoin($builder, $relations, $joinType = 'inner')
    {
        $relations = explode('.', $relations);

        if ($joinType == 'left') {
            $joinMethod = 'leftJoin';
        } elseif ($joinType == 'cross') {
            $joinMethod = 'crossJoin';
        } else {
            $joinMethod = 'join';
        }

        $column = end($relations);
        $baseTable = $this->getTable();
        $baseModel = $this;

        $currentModel = $this;
        $currentPrimaryKey = $this->getKeyName();
        $currentTable = $this->getTable();

        foreach ($relations as $relation) {
            if ($relation == $column) {
                //last item in $relations argument is sort|where column
                continue;
            }

            $relatedRelation = $currentModel->$relation();
            $relatedModel = $relatedRelation->getRelated();
            $relatedPrimaryKey = $relatedModel->getKeyName();
            $relatedTable = $relatedModel->getTable();

            $this->validateJoinQuery($relatedModel);

            if (array_key_exists($relation, $this->joinedTables)) {
                $relatedTableAlias = $this->joinedTables[$relation];
            } else {
                $relatedTableAlias = $this->useTableAlias ? uniqid() : $relatedTable;

                $joinQuery = $relatedTable . ($this->useTableAlias ? ' as ' . $relatedTableAlias : '');
                if ($relatedRelation instanceof BelongsToJoin) {
                    $keyRelated = $relatedRelation->getForeignKey();

                    $builder->$joinMethod($joinQuery, function ($join) use ($relatedTableAlias, $keyRelated, $currentTable, $relatedPrimaryKey, $relatedModel) {
                        $join->on($relatedTableAlias . '.' . $relatedPrimaryKey, '=', $currentTable . '.' . $keyRelated);

                        $this->joinQuery($relatedRelation, $join, $relatedModel, $relatedTableAlias);
                    });
                } elseif ($relatedRelation instanceof HasOneJoin) {
                    $keyRelated = $relatedRelation->getQualifiedForeignKeyName();
                    $keyRelated = last(explode('.', $keyRelated));

                    $builder->$joinMethod($joinQuery, function ($join) use ($relatedTableAlias, $keyRelated, $currentTable, $relatedPrimaryKey, $relatedModel, $currentPrimaryKey) {
                        $join->on($relatedTableAlias . '.' . $keyRelated, '=', $currentTable . '.' . $currentPrimaryKey);

                        $this->joinQuery($relatedRelation, $join, $relatedModel, $relatedTableAlias);
                    });
                } elseif ($relatedRelation instanceof BelongsToManyJoin) {
                    $pivotAlias = uniqid();
                    $builder->$joinMethod($relatedRelation->getTable() . ' AS ' . $pivotAlias, function ($join) use ($relatedRelation, $relatedTableAlias, $currentTable, $relatedPrimaryKey, $relatedModel, $pivotAlias) {
                        $join->on($pivotAlias . '.' . $relatedRelation->getForeignPivotKeyName(), '=', $relatedRelation->getQualifiedParentKeyName());

                        $join->join($relatedRelation->getRelated()->getTable() . ' AS ' . $relatedTableAlias, function ($join) use ($relatedRelation, $relatedTableAlias, $relatedModel, $pivotAlias) {
                            $join->on($relatedTableAlias . '.' . $relatedRelation->getRelated()->getKeyName(), '=', $pivotAlias . '.' . $relatedRelation->getRelatedPivotKeyName());
                            $this->joinQuery($relatedRelation, $join, $relatedModel, $relatedTableAlias, $pivotAlias);
                        });
                    });
                    $this->joinedPivots[$relation] = $pivotAlias;
                } else {
                    throw new EloquentJoinException('Only allowed relations for whereJoin, orWhereJoin and orderByJoin are BelongsToJoin, HasOneJoin and BelongsToManyJoin');
                }
            }

            $currentModel = $relatedModel;
            $currentPrimaryKey = $relatedPrimaryKey;
            $currentTable = $relatedTableAlias;

            $this->joinedTables[$relation] = $relatedTableAlias;
        }

        if (! $this->selected  &&  count($relations) > 1) {
            $this->selected = true;
            $builder->select($baseTable . '.*');
            //$builder->groupBy($baseTable . '.' . $baseModel->primaryKey);
        }

        return $currentTable . '.' . $column;
    }

    private function joinQuery($relatedRelation, $join, $relatedModel, $relatedTableAlias, $pivotAlias = null)
    {
        foreach ($relatedModel->relationWhereClauses as $relationClause) {
            $column = $relationClause['column'];
            $tableAndColumn = explode('.', $column);
            if (count($tableAndColumn) == 1) {
                $column = $relatedTableAlias . '.' . $column;
            } else {
                $table = current($tableAndColumn);
                $column = end($tableAndColumn);
                $joinTableAndAlias = explode(' AS ', $join->table);
                $joinTable = current($joinTableAndAlias);

                if ($table == $joinTable) {
                    $column = $relatedTableAlias . '.' . $column;
                } else if ($relatedRelation instanceof BelongsToManyJoin) {
                    $column = $pivotAlias . '.' . $column;
                }
            }
            $join->where($column, $relationClause['operator'], $relationClause['value'], $relationClause['boolean']);
        }

        if (method_exists($relatedModel, 'getQualifiedDeletedAtColumn')) {
            if ($relatedModel->softDelete == 'withTrashed') {
                //do nothing
            } elseif ($relatedModel->softDelete == 'withoutTrashed') {
                $join->where($relatedTableAlias . '.deleted_at', '=', null);
            } elseif ($relatedModel->softDelete == 'onlyTrashed') {
                $join->where($relatedTableAlias . '.deleted_at', '<>', null);
            }
        }
    }

    private function validateJoinQuery($relatedModel)
    {
        foreach ($relatedModel->relationNotAllowedClauses as $method => $relationNotAllowedClause) {
            throw new EloquentJoinException($method . ' is not allowed on HasOneJoin and BelongsToJoin relations.');
        }

        foreach ($relatedModel->relationWhereClauses as $relationWhereClause) {
            if (empty($relationWhereClause['column'])  ||  ! is_string($relationWhereClause['column'])) {
                throw new EloquentJoinException("Only this where types are allowed on HasOneJoin and BelongsToJoin relations : 
                    ->where('column', 'operator', 'value') 
                    ->where('column', 'value') 
                    ->where(['column' => 'value']) 
                ");
            }
        }
    }
}
