<?php
/*
 * Copyright 2021 Cloud Creativity Limited
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

use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Query\QueryParameters as QueryParametersContract;
use LaravelJsonApi\Contracts\Store\Builder as BuilderContract;
use LaravelJsonApi\Core\Query\QueryParameters;
use LogicException;

trait HasQueryParameters
{

    /**
     * @var Request|null
     */
    private ?Request $request = null;

    /**
     * @var QueryParameters
     */
    private QueryParameters $queryParameters;

    /**
     * @inheritDoc
     */
    public function withRequest(Request $request): BuilderContract
    {
        $this->request = $request;
        $this->queryParameters = QueryParameters::cast($request);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function withQuery(QueryParametersContract $query): BuilderContract
    {
        $this->queryParameters = QueryParameters::cast($query);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function with($includePaths): BuilderContract
    {
        $this->queryParameters->setIncludePaths($includePaths);

        return $this;
    }

    /**
     * @return Request
     */
    private function request(): Request
    {
        if ($this->request) {
            return $this->request;
        }

        throw new LogicException('No HTTP request set: ensure `withRequest()` is called.');
    }
}
