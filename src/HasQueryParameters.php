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

use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Query\QueryParameters as QueryParametersContract;
use LaravelJsonApi\Contracts\Store\Builder as BuilderContract;
use LaravelJsonApi\Core\Query\Custom\ExtendedQueryParameters;

trait HasQueryParameters
{

    /**
     * @var Request|null
     */
    private ?Request $request = null;

    /**
     * @var ExtendedQueryParameters
     */
    private ExtendedQueryParameters $queryParameters;

    /**
     * @inheritDoc
     */
    public function withRequest(Request $request): BuilderContract
    {
        $this->request = $request;
        $this->queryParameters = ExtendedQueryParameters::cast($request);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function withQuery(QueryParametersContract $query): BuilderContract
    {
        $this->queryParameters = ExtendedQueryParameters::cast($query);

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
     * @param mixed $countable
     * @return BuilderContract
     */
    public function withCount($countable): BuilderContract
    {
        $this->queryParameters->setCountable($countable);

        return $this;
    }

}
