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

namespace LaravelJsonApi\Eloquent\Tests\Acceptance;

use App\Schemas;
use Illuminate\Support\Arr;
use LaravelJsonApi\Contracts\Schema\Container as SchemaContainerContract;
use LaravelJsonApi\Contracts\Server\Server;
use LaravelJsonApi\Core\Schema\Container as SchemaContainer;
use LaravelJsonApi\Core\Schema\TypeResolver;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        $this->app->singleton(
            SchemaContainerContract::class,
            fn($container) => new SchemaContainer($container, $container->make(Server::class), [
                Schemas\CarOwnerSchema::class,
                Schemas\CarSchema::class,
                Schemas\CommentSchema::class,
                Schemas\CountrySchema::class,
                Schemas\ImageSchema::class,
                Schemas\MechanicSchema::class,
                Schemas\PhoneSchema::class,
                Schemas\PostSchema::class,
                Schemas\RoleSchema::class,
                Schemas\TagSchema::class,
                Schemas\UserAccountSchema::class,
                Schemas\UserSchema::class,
                Schemas\VideoSchema::class,
            ])
        );

        $this->app->singleton(Server::class, function () {
            $server = $this->createMock(Server::class);
            $server->method('schemas')->willReturnCallback(fn() => $this->schemas());
            return $server;
        });
    }

    /**
     * @return SchemaContainerContract
     */
    protected function schemas(): SchemaContainerContract
    {
        return $this->app->make(SchemaContainerContract::class);
    }

    /**
     * @return Server
     */
    protected function server(): Server
    {
        return $this->app->make(Server::class);
    }

    /**
     * @param string $class
     * @param string|string[] $paths
     * @return void
     */
    protected function createSchemaWithDefaultEagerLoading(string $class, $paths): void
    {
        $mock = $this
            ->getMockBuilder($class)
            ->setConstructorArgs(['server' => $this->server()])
            ->onlyMethods(['with'])
            ->getMock();

        $resolver = new TypeResolver();
        $resolver->register(get_class($mock), ($resolver)($class));

        $mock->method('with')->willReturn(Arr::wrap($paths));

        $this->app->bind($class, static fn() => $mock);
    }
}
