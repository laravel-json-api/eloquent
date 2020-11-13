<?php
/**
 * Copyright 2020 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Eloquent;

use IteratorAggregate;
use LaravelJsonApi\Contracts\Schema\Container;
use LaravelJsonApi\Core\Query\RelationshipPath;
use LaravelJsonApi\Eloquent\Fields\Relations\MorphTo;
use LaravelJsonApi\Eloquent\Fields\Relations\Relation;
use LogicException;
use function implode;
use function iterator_to_array;

class EagerLoadPath implements IteratorAggregate
{

    /**
     * @var Container
     */
    private Container $schemas;

    /**
     * @var Schema
     */
    private Schema $schema;

    /**
     * @var RelationshipPath
     */
    private RelationshipPath $path;

    /**
     * @var bool
     */
    private bool $skipMissingFields = false;

    /**
     * EagerLoadPath constructor.
     *
     * @param Container $schemas
     * @param Schema $schema
     * @param RelationshipPath $path
     */
    public function __construct(Container $schemas, Schema $schema, RelationshipPath $path)
    {
        $this->schemas = $schemas;
        $this->schema = $schema;
        $this->path = $path;
    }

    /**
     * @param bool $skip
     * @return $this
     */
    public function skipMissingFields(bool $skip = true): self
    {
        $this->skipMissingFields = $skip;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return implode('.', $this->all());
    }

    /**
     * @return array
     */
    public function all(): array
    {
        return iterator_to_array($this);
    }

    /**
     * @inheritDoc
     */
    public function getIterator()
    {
        $schema = $this->schema;

        foreach ($this->path->names() as $idx => $field) {
            if ($this->skipMissingFields && false === $schema->isRelationship($field)) {
                break;
            }

            $relation = $schema->relationship($field);

            if (!$relation->isIncludePath()) {
                throw new LogicException(sprintf(
                    'Unsupported include field %s in path %s.',
                    $field,
                    $this->path
                ));
            }

            /**
             * If we have a morph to relation, we will only yield the
             * relation name if we are at the end of the relationship
             * path. Otherwise we need to use a morph map.
             */
            if ($relation instanceof MorphTo) {
                if ($idx === ($this->path->count() - 1)) {
                    yield $relation->relationName();
                }
                break;
            }

            if ($relation instanceof Relation) {
                $schema = $this->schemas->schemaFor($relation->inverse());
                yield $relation->relationName();
                continue;
            }

            throw new LogicException(sprintf(
                'Field %s in path %s is not an Eloquent include path.',
                $field,
                $this->path
            ));
        }
    }

}
