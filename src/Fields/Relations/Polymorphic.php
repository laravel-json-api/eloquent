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

namespace LaravelJsonApi\Eloquent\Fields\Relations;

use Generator;
use Illuminate\Database\Eloquent\Model;
use LaravelJsonApi\Eloquent\Schema;
use LogicException;
use UnexpectedValueException;
use function get_class;
use function sprintf;

trait Polymorphic
{

    /**
     * Get the inverse schema for the provided model.
     *
     * @param Model $model
     * @return Schema
     */
    public function schemaFor(Model $model): Schema
    {
        $class = get_class($model);

        /** @var Schema $schema */
        foreach ($this->allSchemas() as $schema) {
            if ($schema->isModel($model)) {
                return $schema;
            }
        }

        throw new UnexpectedValueException(sprintf(
            'Model %s is not valid for polymorphic relation %s.',
            $class,
            $this->name()
        ));
    }

    /**
     * @return Generator
     */
    public function allSchemas(): Generator
    {
        foreach ($this->inverseTypes() as $type) {
            $schema = $this->schemas()->schemaFor($type);

            if ($schema instanceof Schema) {
                yield $type => $schema;
                continue;
            }

            throw new LogicException(sprintf(
                'Expecting schema for resource type %s to be an Eloquent schema.',
                $type
            ));
        }
    }
}
