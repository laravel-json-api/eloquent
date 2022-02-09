<?php
/*
 * Copyright 2022 Cloud Creativity Limited
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

namespace LaravelJsonApi\Eloquent\QueryBuilder\EagerLoading;

use IteratorAggregate;
use LaravelJsonApi\Contracts\Schema\Container;
use LaravelJsonApi\Core\Query\IncludePaths;
use LaravelJsonApi\Core\Query\RelationshipPath;
use LaravelJsonApi\Eloquent\Fields\Relations\MorphTo;
use LaravelJsonApi\Eloquent\Schema;
use Traversable;

/**
 * Class EagerLoadMorphs
 *
 * @internal
 */
class EagerLoadMorphs implements IteratorAggregate
{

    /**
     * @var Container
     */
    private Container $schemas;

    /**
     * @var MorphTo
     */
    private MorphTo $relation;

    /**
     * @var IncludePaths
     */
    private IncludePaths $paths;

    /**
     * EagerLoadMorphPath constructor.
     *
     * @param Container $schemas
     * @param MorphTo $relation
     * @param IncludePaths $paths
     */
    public function __construct(Container $schemas, MorphTo $relation, IncludePaths $paths)
    {
        $this->schemas = $schemas;
        $this->relation = $relation;
        $this->paths = $paths;
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return $this->relation->relationName();
    }

    /**
     * @return array
     */
    public function all(): array
    {
        return array_filter(iterator_to_array($this));
    }

    /**
     * @inheritDoc
     */
    public function getIterator(): Traversable
    {
        foreach ($this->relation->allSchemas() as $schema) {
            $loader = new EagerLoader($this->schemas, $schema, $this->pathsFor($schema));

            yield $schema->model() => $loader->getRelations();
        }
    }

    /**
     * Get the paths that are valid for the provided schema.
     *
     * Paths are only valid for the provided schema if the first relation in the include
     * path exists on the provided schema. Otherwise it needs to be skipped.
     *
     * @param Schema $schema
     * @return IncludePaths
     */
    private function pathsFor(Schema $schema): IncludePaths
    {
        return $this->paths->filter(
            fn(RelationshipPath $path) => $schema->isRelationship($path->first())
        );
    }

}
