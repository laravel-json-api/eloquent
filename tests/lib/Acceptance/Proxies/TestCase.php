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
