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

namespace LaravelJsonApi\Eloquent\Contracts;

use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Database\Eloquent\Model;
use LaravelJsonApi\Contracts\Resources\Creatable;

interface Proxy extends Creatable, UrlRoutable
{

    /**
     * Create a new proxy for the supplied model.
     *
     * @param Model $model
     * @return Proxy
     */
    public static function proxyFor(Model $model): Proxy;

    /**
     * Create a nullable proxy for the supplied model.
     *
     * @param Model|null $model
     * @return Proxy|null
     */
    public static function nullable(?Model $model): ?Proxy;

    /**
     * Create an iterator of proxies.
     *
     * @param mixed $models
     * @return iterable
     */
    public static function iterator($models): iterable;

    /**
     * Wrap the provided model or models.
     *
     * @param Model|mixed|null $modelOrModels
     * @return mixed
     */
    public static function wrap($modelOrModels);

    /**
     * Get the model the proxy is wrapping.
     *
     * @return Model
     */
    public function toBase(): Model;
}
