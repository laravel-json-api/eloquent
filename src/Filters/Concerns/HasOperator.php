<?php


namespace LaravelJsonApi\Eloquent\Filters\Concerns;

trait HasOperator {

    /**
     * @var string
     */
    private string $operator;

    /**
     * @return $this
     */
    public function eq(): self
    {
        return $this->using('=');
    }

    /**
     * @return $this
     */
    public function gt(): self
    {
        return $this->using('>');
    }

    /**
     * @return $this
     */
    public function gte(): self
    {
        return $this->using('>=');
    }

    /**
     * @return $this
     */
    public function lt(): self
    {
        return $this->using('<');
    }

    /**
     * @return $this
     */
    public function lte(): self
    {
        return $this->using('<=');
    }

    /**
     * Use the provided operator for the filter.
     *
     * @param string $operator
     * @return $this
     */
    public function using(string $operator): self
    {
        $this->operator = $operator;

        return $this;
    }

    /**
     * @return string
     */
    public function operator(): string
    {
        return $this->operator;
    }
}
