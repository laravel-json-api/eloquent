<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Eloquent\Tests\Acceptance;

use App\Schemas;
use Illuminate\Foundation\Testing\Concerns\InteractsWithDeprecationHandling;
use Illuminate\Support\Arr;
use LaravelJsonApi\Contracts\Schema\Container as SchemaContainerContract;
use LaravelJsonApi\Contracts\Server\Server;
use LaravelJsonApi\Core\Schema\Container as SchemaContainer;
use LaravelJsonApi\Core\Schema\TypeResolver;
use LaravelJsonApi\Core\Support\ContainerResolver;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    use InteractsWithDeprecationHandling;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutDeprecationHandling();

        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        $this->app->singleton(SchemaContainerContract::class, function ($container) {
            $resolver = new ContainerResolver(static fn() => $container);
            return new SchemaContainer($resolver, $container->make(Server::class), [
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
            ]);
        });

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
