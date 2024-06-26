<?php

declare(strict_types=1);

namespace LaravelJsonApi\Eloquent\Pagination\Cursor;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\Cursor as LaravelCursor;
use LaravelJsonApi\Contracts\Schema\ID;
use LaravelJsonApi\Core\Schema\IdParser;

class CursorBuilder
{
    private Builder|Relation $query;

    private ?ID $id = null;

    private string $keyName;

    private string $direction;

    private ?int $defaultPerPage = null;

    private bool $withTotal;

    private bool $keySort = true;

    /**
     * CursorBuilder constructor.
     *
     * @param Builder|Relation $query
     *      the column to use for the cursor
     * @param string|null $key
     *      the key column that the before/after cursors related to
     */
    public function __construct($query, string $key = null)
    {
        if (!$query instanceof Builder && !$query instanceof Relation) {
            throw new \InvalidArgumentException('Expecting an Eloquent query builder or relation.');
        }

        $this->query = $query;
        $this->keyName = $key ?: $this->guessKey();
    }

    /**
     * Set the default number of items per-page.
     *
     * If null, the default from the `Model::getPage()` method will be used.
     *
     * @return $this
     */
    public function withDefaultPerPage(?int $perPage): self
    {
        $this->defaultPerPage = $perPage;

        return $this;
    }

    /**
     * @return $this
     */
    public function withIdField(?ID $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function withKeySort(bool $keySort): self
    {
        $this->keySort = $keySort;

        return $this;
    }

    /**
     * Set the query direction.
     *
     * @return $this
     */
    public function withDirection(string $direction): self
    {
        if (\in_array($direction, ['asc', 'desc'])) {
            $this->direction = $direction;

            return $this;
        }

        throw new \InvalidArgumentException('Unexpected query direction.');
    }

    public function withTotal(bool $withTotal): self
    {
        $this->withTotal = $withTotal;

        return $this;
    }

    /**
     * @param array<string> $columns
     */
    public function paginate(Cursor $cursor, array $columns = ['*']): CursorPaginator
    {
        $cursor = $cursor->withDefaultLimit($this->getDefaultPerPage());

        $this->applyKeySort();

        $total = $this->getTotal();
        $laravelPaginator = $this->query->cursorPaginate($cursor->getLimit(), $columns, 'cursor', $this->convertCursor($cursor));
        $paginator = new CursorPaginator($laravelPaginator, $cursor, $total);

        return $paginator->withCurrentPath();
    }

    private function applyKeySort(): void
    {
        if (!$this->keySort) {
            return;
        }

        if (
            empty($this->query->getQuery()->orders)
            || collect($this->query->getQuery()->orders)
                ->whereIn('column', [$this->keyName, $this->query->qualifyColumn($this->keyName)])
                ->isEmpty()
        ) {
            $this->query->orderBy($this->keyName, $this->direction);
        }
    }

    private function getTotal(): ?int
    {
        return $this->withTotal ? $this->query->count() : null;
    }

    private function convertCursor(Cursor $cursor): ?LaravelCursor
    {
        $encodedCursor = $cursor->isBefore() ? $cursor->getBefore() : $cursor->getAfter();
        if (!is_string($encodedCursor)) {
            return null;
        }

        $parameters = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $encodedCursor)), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        $pointsToNextItems = $parameters['_pointsToNextItems'];
        unset($parameters['_pointsToNextItems']);
        if (isset($parameters[$this->keyName])) {
            $parameters[$this->keyName] = IdParser::make($this->id)->decode(
                (string) $parameters[$this->keyName],
            );
        }

        return new LaravelCursor($parameters, $pointsToNextItems);
    }

    private function getDefaultPerPage(): int
    {
        if (is_int($this->defaultPerPage)) {
            return $this->defaultPerPage;
        }

        return $this->query->getModel()->getPerPage();
    }

    /**
     * Guess the key to use for the cursor.
     */
    private function guessKey(): string
    {
        return $this->id?->key() ?? $this->query->getModel()->getKeyName();
    }
}
