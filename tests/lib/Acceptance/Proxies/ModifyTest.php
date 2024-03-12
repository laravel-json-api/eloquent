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

use App\Models\Image;
use App\Models\Phone;
use App\Models\Role;
use App\Models\User;
use App\Models\UserAccount;

class ModifyTest extends TestCase
{

    public function testCreate(): void
    {
        User::creating(function (User $user) {
            $user->password = bcrypt('secret');
        });

        $role = Role::factory()->create();

        $user = User::factory()->make();

        $data = [
            'email' => $user->email,
            'name' => $user->name,
            'roles' => [
                ['type' => 'roles', 'id' => (string) $role->getRouteKey()],
            ],
        ];

        $actual = $this->schema
            ->repository()
            ->create()
            ->store($data);

        $this->assertInstanceOf(UserAccount::class, $actual);
        $this->assertDatabaseHas('users', array_merge([
            $user->getKeyName() => $actual->getKey(),
        ], $data));

        $this->assertDatabaseCount('role_user', 1);
        $this->assertDatabaseHas('role_user', [
            'role_id' => $role->getKey(),
            'user_id' => $actual->getKey(),
        ]);
    }

    public function testUpdate(): void
    {
        $user = User::factory()->create();
        $phone = Phone::factory()->create();

        $data = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'phone' => [
                'type' => 'phones',
                'id' => (string) $phone->getRouteKey(),
            ],
        ];

        $actual = $this->schema
            ->repository()
            ->update($user)
            ->with('phone')
            ->store($data);

        $this->assertInstanceOf(UserAccount::class, $actual);
        $this->assertTrue($user->is($actual->toBase()));
        $this->assertTrue($phone->is($actual->phone));

        $this->assertDatabaseHas('phones', [
            $phone->getKeyName() => $phone->getKey(),
            'user_id' => $actual->getKey(),
        ]);
    }

    public function testToOne(): void
    {
        $phone = Phone::factory()->create();
        $user = User::factory()->create();

        $schema = $this->schemas()->schemaFor('phones');

        $actual = $schema
            ->repository()
            ->modifyToOne($phone, 'userAccount')
            ->associate(['type' => 'user-accounts', 'id' => (string) $user->getRouteKey()]);

        $this->assertInstanceOf(UserAccount::class, $actual);
        $this->assertTrue($user->is($actual->toBase()));
    }

    public function testMorphTo(): void
    {
        $user = User::factory()->create();
        $image = Image::factory()->create();

        $actual = $this
            ->imageSchema()
            ->repository()
            ->modifyToOne($image, 'imageable')
            ->associate(['type' => 'user-accounts', 'id' => (string) $user->getRouteKey()]);

        $this->assertInstanceOf(UserAccount::class, $actual);
        $this->assertTrue($user->is($actual->toBase()));

        $this->assertDatabaseHas('images', [
            $image->getKeyName() => $image->getKey(),
            'imageable_type' => User::class,
            'imageable_id' => $user->getKey(),
        ]);
    }

    public function testSyncToMany(): void
    {
        $role = Role::factory()->has(User::factory()->count(2))->create();
        $users = User::factory()->count(2)->create();

        $schema = $this->schemas()->schemaFor('roles');

        $ids = $users
            ->map(fn(User $user) => ['type' => 'user-accounts', 'id' => (string) $user->getRouteKey()])
            ->all();

        $actual = $schema
            ->repository()
            ->modifyToMany($role, 'userAccounts')
            ->sync($ids);

        $this->assertUserAccounts($users, $actual);
        $this->assertDatabaseCount('role_user', 2);

        foreach ($users as $user) {
            $this->assertDatabaseHas('role_user', [
                'role_id' => $role->getKey(),
                'user_id' => $user->getKey(),
            ]);
        }
    }

    public function testAttachToMany(): void
    {
        $role = Role::factory()->has(User::factory()->count(2))->create();
        $existing = $role->users()->get();
        $users = User::factory()->count(2)->create();

        $schema = $this->schemas()->schemaFor('roles');

        $ids = $users
            ->map(fn(User $user) => ['type' => 'user-accounts', 'id' => (string) $user->getRouteKey()])
            ->all();

        $actual = $schema
            ->repository()
            ->modifyToMany($role, 'userAccounts')
            ->attach($ids);

        $this->assertUserAccounts($users, $actual);
        $this->assertDatabaseCount('role_user', 4);

        foreach ($users->merge($existing) as $user) {
            $this->assertDatabaseHas('role_user', [
                'role_id' => $role->getKey(),
                'user_id' => $user->getKey(),
            ]);
        }
    }

    public function testDetachToMany(): void
    {
        $role = Role::factory()->has(User::factory()->count(3))->create();
        $existing = $role->users()->get();
        $expected = $existing->last();

        $schema = $this->schemas()->schemaFor('roles');

        $ids = $existing
            ->take(2)
            ->map(fn(User $user) => ['type' => 'user-accounts', 'id' => (string) $user->getRouteKey()])
            ->all();

        $actual = $schema
            ->repository()
            ->modifyToMany($role, 'userAccounts')
            ->detach($ids);

        $this->assertUserAccounts($existing->take(2), $actual);
        $this->assertDatabaseCount('role_user', 1);

        $this->assertDatabaseHas('role_user', [
            'role_id' => $role->getKey(),
            'user_id' => $expected->getKey(),
        ]);
    }
}
