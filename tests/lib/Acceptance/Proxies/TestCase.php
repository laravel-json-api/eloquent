<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Eloquent\Tests\Acceptance\Proxies;

use App\Models\UserAccount;
use App\Schemas\ImageSchema;
use App\Schemas\UserAccountSchema;
use Illuminate\Contracts\Routing\UrlRoutable;
use LaravelJsonApi\Eloquent\Fields\Relations\MorphTo;
use LaravelJsonApi\Eloquent\Tests\Acceptance\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{

    /**
     * @var UserAccountSchema
     */
    protected UserAccountSchema $schema;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->schema = $this->app->make(UserAccountSchema::class);
    }

    /**
     * @param $expected
     * @param $actual
     * @return void
     */
    protected function assertUserAccounts($expected, $actual): void
    {
        $expected = collect($expected)
            ->map(fn(UrlRoutable $routable) => $routable->getRouteKey())
            ->sort()
            ->values()
            ->all();

        $actual = collect($actual)
            ->each(fn($account) => $this->assertInstanceOf(UserAccount::class, $account))
            ->map(fn(UserAccount $account) => $account->getRouteKey())
            ->sort()
            ->values()
            ->all();

        $this->assertSame($expected, $actual);
    }

    /**
     * Get the image schema, but altered to return user-accounts from its imageable relationship.
     *
     * @return ImageSchema
     */
    protected function imageSchema(): ImageSchema
    {
        $schema = $this->schemas()->schemaFor('images');

        /** @var MorphTo $relation */
        $relation = $schema->relationship('imageable');

        /** Modify the relationship to use user-accounts, not users. */
        $relation->types('posts', 'user-accounts');

        return $schema;
    }
}
