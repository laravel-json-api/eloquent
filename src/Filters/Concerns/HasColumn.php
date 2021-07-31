<?php


namespace LaravelJsonApi\Eloquent\Filters\Concerns;


trait HasColumn {

    /**
     * @var string
     */
    private string $column;

    /**
     * @return string
     */
    public function column(): string
    {
        return $this->column;
    }
}
