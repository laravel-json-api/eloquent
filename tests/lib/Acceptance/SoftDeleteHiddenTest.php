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

use App\Models\Country;
use App\Schemas\CountrySchema;
use Carbon\Carbon;

class SoftDeleteHiddenTest extends TestCase
{

    /**
     * @var CountrySchema
     */
    private CountrySchema $schema;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        /**
         * The country model uses the Eloquent soft-deletes trait, but it does not
         * implement the JSON:API soft-deletes trait on its schema. This means
         * soft-deleted countries should not exist in the API.
         */
        $this->schema = $this->app->make(CountrySchema::class);
    }

    public function testFindAndExistsWithTrashed(): void
    {
        $country = Country::factory()->create(['deleted_at' => Carbon::now()]);

        $this->assertNull(
            $this->schema->repository()->find((string) $country->getRouteKey())
        );

        $this->assertFalse(
            $this->schema->repository()->exists((string) $country->getRouteKey())
        );
    }

    public function testFindAndExistsWithNotTrashed(): void
    {
        $country = Country::factory()->create(['deleted_at' => null]);

        $actual = $this->schema->repository()->find((string) $country->getRouteKey());

        $this->assertTrue($country->is($actual));

        $this->assertTrue(
            $this->schema->repository()->exists((string) $country->getRouteKey())
        );
    }

    public function testFindMany(): void
    {
        $countries = Country::factory()->count(5)->sequence(
            ['deleted_at' => null],
            ['deleted_at' => Carbon::now()],
        )->create();

        $ids = $countries
            ->map(fn(Country $country) => (string) $country->getRouteKey())
            ->all();

        $expected = $countries
            ->filter(fn(Country $country) => is_null($country->deleted_at))
            ->sortBy('id')
            ->pluck('id')
            ->all();

        $actual = $this->schema
            ->repository()
            ->findMany($ids);

        $this->assertCount(count($expected), $actual);

        $this->assertSame(
            $expected,
            collect($actual)->sortBy('id')->pluck('id')->all()
        );
    }

    public function testDelete(): void
    {
        $country = Country::factory()->create(['deleted_at' => null]);

        $this->schema->repository()->delete($country);

        $this->assertSoftDeleted($country);
    }
}
