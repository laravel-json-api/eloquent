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

use App\Models\Image;
use App\Models\Phone;
use App\Models\Role;
use App\Models\User;
use App\Models\UserAccount;

class QueryTest extends TestCase
{

    public function testFind(): void
    {
        $model = User::factory()->create();

        $account = $this->schema
            ->repository()
            ->find((string) $model->getRouteKey());

        $this->assertInstanceOf(UserAccount::class, $account);
        $this->assertTrue($model->is($account->toBase()));
    }

    public function testFindDoesNotExist(): void
    {
        $account = $this->schema
            ->repository()
            ->find('99999');

        $this->assertNull($account);
    }

    public function testExists(): void
    {
        $model = User::factory()->create();

        $this->assertFalse($this->schema->repository()->exists('99999'));
        $this->assertTrue($this->schema->repository()->exists((string) $model->getRouteKey()));
    }

    public function testFindMany(): void
    {
        $models = User::factory()->count(5)->create();

        $expected = $models->random(3);

        $ids = $expected
            ->map(fn(User $user) => (string) $user->getRouteKey())
            ->all();

        $actual = $this->schema->repository()->findMany($ids);

        $this->assertUserAccounts($expected, $actual);
    }

    public function testQueryOne(): void
    {
        $model = User::factory()->create();

        $actual = $this->schema
            ->repository()
            ->queryOne((string) $model->getRouteKey())
            ->first();

        $this->assertInstanceOf(UserAccount::class, $actual);
        $this->assertTrue($model->is($actual->toBase()));
    }

    public function testQueryOneWithProxy(): void
    {
        $model = User::factory()->create();

        $phone = Phone::factory()
            ->for($model)
            ->create();

        $actual = $this->schema
            ->repository()
            ->queryOne(new UserAccount($model))
            ->with('phone')
            ->first();

        $this->assertInstanceOf(UserAccount::class, $actual);
        $this->assertTrue($model->is($actual->toBase()));
        $this->assertTrue($actual->relationLoaded('phone'));
        $this->assertTrue($phone->is($actual->phone));
    }

    public function testQueryOneDoesNotExist(): void
    {
        $actual = $this->schema
            ->repository()
            ->queryOne('999999')
            ->first();

        $this->assertNull($actual);
    }

    public function testQueryAll(): void
    {
        $models = User::factory()->count(5)->create();

        $actual = $this->schema
            ->repository()
            ->queryAll()
            ->with('phone')
            ->get();

        $this->assertUserAccounts($models, $actual);
        $this->assertTrue($actual[0]->relationLoaded('phone'));
    }

    public function testQueryAllWithPagination(): void
    {
        $models = User::factory()->count(5)->create();

        $expected = $models->sortBy('id')->take(2);

        $actual = $this->schema
            ->repository()
            ->queryAll()
            ->sort('id')
            ->paginate(['number' => 1, 'size' => 2]);

        $this->assertUserAccounts($expected, $actual);
        $this->assertEquals([
            'page' => [
                'currentPage' => 1,
                'from' => 1,
                'lastPage' => 3,
                'perPage' => 2,
                'to' => 2,
                'total' => 5,
            ],
        ], $actual->meta());
    }

    public function testQueryToOne(): void
    {
        $model = User::factory()->create();

        $phone = Phone::factory()
            ->for($model)
            ->create();

        $actual = $this->schema
            ->repository()
            ->queryToOne(new UserAccount($model), 'phone')
            ->first();

        $this->assertTrue($phone->is($actual));
    }

    public function testQueryToOneReversed(): void
    {
        $model = User::factory()->create();

        $phone = Phone::factory()
            ->for($model)
            ->create();

        $schema = $this->schemas()->schemaFor('phones');

        $actual = $schema
            ->repository()
            ->queryToOne($phone, 'userAccount')
            ->with('roles')
            ->first();

        $this->assertInstanceOf(UserAccount::class, $actual);
        $this->assertTrue($model->is($actual->toBase()));
        $this->assertTrue($actual->relationLoaded('roles'));
    }

    public function testQueryMorphTo(): void
    {
        $user = User::factory()->create();
        $image = Image::factory()->for($user, 'imageable')->create();

        $schema = $this->imageSchema();

        $actual = $schema
            ->repository()
            ->queryToOne($image, 'imageable')
            ->first();

        $this->assertInstanceOf(UserAccount::class, $actual);
        $this->assertTrue($user->is($actual->toBase()));
    }

    public function testQueryToMany(): void
    {
        $model = User::factory()
            ->has(Role::factory()->count(3))
            ->create();

        $actual = $this->schema
            ->repository()
            ->queryToMany(new UserAccount($model), 'roles')
            ->get();

        $this->assertCount(3, $actual);
    }

    public function testQueryToManyReversed(): void
    {
        $model = User::factory()
            ->has(Role::factory()->count(1))
            ->create();

        $role = $model->roles()->first();

        $schema = $this->schemas()->schemaFor('roles');

        $actual = $schema
            ->repository()
            ->queryToMany($role, 'userAccounts')
            ->get();

        $this->assertUserAccounts([$model], $actual);
    }
}
