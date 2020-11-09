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

namespace LaravelJsonApi\Eloquent\Tests\Acceptance;

use LaravelJsonApi\Contracts\Schema\Container as SchemaContainerContract;
use LaravelJsonApi\Core\Schema\Container as SchemaContainer;
use Orchestra\Testbench\TestCase as BaseTestCase;
use App\Schemas;

class TestCase extends BaseTestCase
{

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        $this->app->singleton(SchemaContainerContract::class, fn($container) => new SchemaContainer($container, [
            Schemas\PhoneSchema::class,
            Schemas\UserSchema::class,
        ]));
    }

    /**
     * @return SchemaContainerContract
     */
    protected function schemas(): SchemaContainerContract
    {
        return $this->app->make(SchemaContainerContract::class);
    }
}
