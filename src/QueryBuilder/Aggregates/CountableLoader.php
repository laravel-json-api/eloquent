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

namespace LaravelJsonApi\Eloquent\QueryBuilder\Aggregates;

use Generator;
use LaravelJsonApi\Core\Query\Custom\CountablePaths;
use LaravelJsonApi\Eloquent\Contracts\Countable;
use LaravelJsonApi\Eloquent\Fields\Relations\MorphToMany;
use LaravelJsonApi\Eloquent\Schema;
use LogicException;
use function sprintf;

class CountableLoader
{

    /**
     * @var Schema
     */
    private Schema $schema;

    /**
     * @var CountablePaths
     */
    private CountablePaths $paths;

    /**
     * CountableLoader constructor.
     *
     * @param Schema $schema
     * @param CountablePaths $paths
     */
    public function __construct(Schema $schema, CountablePaths $paths)
    {
        $this->schema = $schema;
        $this->paths = $paths;
    }

    /**
     * @return array
     */
    public function getRelations(): array
    {
        $relations = [];

        foreach ($this->paths as $path) {
            $relation = $this->schema->relationship($path);

            if ($relation instanceof Countable && $relation->isCountable()) {
                foreach ($this->relationsFor($relation) as $name) {
                    $relations[] = $name;
                }
                continue;
            }

            throw new LogicException(sprintf(
                'Field %s is not a countable Eloquent relation on schema %s.',
                $path,
                $this->schema->type(),
            ));
        }

        return $relations;
    }

    /**
     * Yield the countable relations for the provided relationship.
     *
     * @param Countable $relation
     * @return Generator
     */
    private function relationsFor(Countable $relation): Generator
    {
        if ($relation instanceof MorphToMany) {
            foreach ($relation as $child) {
                // do not check whether the child is countable because the parent is.
                if ($child instanceof Countable) {
                    yield $child->withCountName();
                }
            }
            return;
        }

        yield $relation->withCountName();
    }

}
