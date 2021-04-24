<?php

namespace LaravelJsonApi\Eloquent\Filters;

use LaravelJsonApi\Eloquent\Contracts\Filter;
use LaravelJsonApi\Eloquent\Schema;

class WhereHas implements Filter
{
    use Concerns\DeserializesValue;
    use Concerns\IsSingular;

    /**
     * @var string
     */
    private string $relationName;

    private Schema $schema;

    /**
     * SearchFilter constructor.
     *
     * @param string $relationName
     * @param array $columns
     */
    public function __construct(string $relationName, Schema $schema)
    {
        $this->relationName = $relationName;
        $this->schema = $schema;

        if (!$this->schema->isRelationship($relationName)) {
            throw new \LogicException("Relationship with name $relationName not defined in " . get_class($schema) . " schema.");
        }
    }

    /**
     * Create a new filter.
     *
     * @param string $relationName
     * @param Schema $schema
     * @return WhereHas
     */
    public static function make(string $relationName, Schema $schema): self
    {
        return new static($relationName, $schema);
    }

    /**
     * @inheritDoc
     */
    public function key(): string
    {
        return $this->relationName;
    }

    /**
     * Apply the filter to the query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param mixed $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function apply($query, $value)
    {
        $deserializedValues = $this->deserialize($value);

        $relation = $this->schema->relationship($this->relationName);

        $availableFilters = collect($relation->schema()->filters())->merge($relation->filters());

        $keyedFilters = collect($availableFilters)->keyBy(function ($filter) {
            return $filter->key();
        })->all();

        return $query->whereHas($this->relationName, function ($query) use ($deserializedValues, $keyedFilters) {
            foreach ($deserializedValues as $key => $value) {
                if (isset($keyedFilters[$key])) {
                    $keyedFilters[$key]->apply($query, $value);
                }
            }
        });
    }
}
