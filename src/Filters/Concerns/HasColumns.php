<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Eloquent\Filters\Concerns;

trait HasColumns
{

    /**
     * @var string|null
     */
    private ?string $table = null;


    /**
     * @var array<string>
     */
    private array $columns = [];

    /**
     * @return array<string>
     */
    public function columns(): array
    {
        return $this->columns;
    }

    public function withColumn(string $column): self
    {
        $this->columns[] = $column;

        return $this;
    }

    /**
     * Force the table name when qualifying the columns.
     *
     * This allows the developer to force the table that the columns are qualified with.
     *
     * @param string $table
     * @return $this
     */
    public function qualifyAs(string $table): self
    {
        $this->table = $table;

        return $this;
    }

    /**
     * Determine if developer has forced a table to qualify columns as
     *
     * @return bool
     */
    public function isQualified(): bool
    {
        return $this->table === null;
    }

    /**
     * Get qualified columns.
     *
     * @return array<string>
     */
    protected function qualifiedColumns(): array
    {
        if ($this->table) {
            return array_map(fn($column) => $this->table . '.' . $column, $this->columns);
        }

        return $this->columns;
    }
}
