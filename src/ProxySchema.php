<?php
/*
 * Copyright 2023 Cloud Creativity Limited
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

use Illuminate\Database\Eloquent\Model;
use LaravelJsonApi\Eloquent\Contracts\Parser;
use LaravelJsonApi\Eloquent\Contracts\Proxy as ProxyContract;
use LaravelJsonApi\Eloquent\Parsers\ProxyParser;

abstract class ProxySchema extends Schema
{

    /**
     * @return Parser
     */
    public function parser(): Parser
    {
        if ($this->parser) {
            return $this->parser;
        }

        return $this->parser = new ProxyParser($this->newProxy());
    }

    /**
     * @inheritDoc
     */
    public function newInstance(): Model
    {
        return $this->newProxy()->toBase();
    }

    /**
     * @inheritDoc
     */
    public function isModel($model): bool
    {
        $expected = get_class($this->newInstance());

        return ($model instanceof $expected) || $expected === $model;
    }

    /**
     * Create a new proxy.
     *
     * @param Model|null $model
     * @return ProxyContract
     */
    public function newProxy(Model $model = null): ProxyContract
    {
        $proxyClass = $this->model();

        return new $proxyClass($model);
    }
}
