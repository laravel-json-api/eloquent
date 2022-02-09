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

namespace LaravelJsonApi\Eloquent\Fields\Concerns;

use Illuminate\Database\Eloquent\Model;
use LogicException;
use function get_class;
use function sprintf;

trait OnRelated
{
    /**
     * @var string|null
     */
    private ?string $related = null;

    /**
     * Get the default eager load path for the attribute.
     *
     * @return string|string[]|null
     */
    public function with()
    {
        return $this->related;
    }

    /**
     * Set the attribute as existing on a related model.
     *
     * @param string $related
     * @return $this
     */
    public function on(string $related): self
    {
        $this->related = $related;

        return $this;
    }

    /**
     * Must the model exist in the database before the attribute is filled?
     *
     * @return bool
     */
    public function mustExist(): bool
    {
        return !is_null($this->related);
    }

    /**
     * Get the model that the attribute exists on (the "owner" of the attribute).
     *
     * @param Model $model
     * @return Model
     */
    protected function owner(Model $model): Model
    {
        if ($this->related && $related = $model->{$this->related}) {
            return $related;
        }

        if ($this->related) {
            throw new LogicException(sprintf(
                'Expecting relationship %s on %s to use `withDefault()` to ensure there is always a related model.',
                $this->related,
                get_class($model),
            ));
        }

        return $model;
    }
}
