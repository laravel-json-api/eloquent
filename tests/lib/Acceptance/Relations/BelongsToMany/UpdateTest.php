<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Eloquent\Tests\Acceptance\Relations\BelongsToMany;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class UpdateTest extends TestCase
{

    public function test(): void
    {
        $this->actingAs(User::factory()->create(['admin' => true]));

        /** @var User $user */
        $user = User::factory()
            ->has(Role::factory()->count(3))
            ->create();

        $existing = $user->roles()->get();
        $remove = $existing->last();

        $expected = $existing->take(2)->push(
            Role::factory()->create()
        );

        $this->repository->update($user)->store([
            'roles' => $expected->map(fn(Role $role) => [
                'type' => 'roles',
                'id' => (string) $role->getRouteKey(),
            ])->all(),
        ]);

        $this->assertTrue($user->relationLoaded('roles'));
        $this->assertInstanceOf(EloquentCollection::class, $actual = $user->getRelation('roles'));
        $this->assertRoles($expected, $actual);

        foreach ($expected as $role) {
            $this->assertDatabaseHas('role_user', [
                'approved' => true,
                'role_id' => $role->getKey(),
                'user_id' => $user->getKey(),
            ]);
        }

        $this->assertDatabaseMissing('role_user', [
            'role_id' => $remove->getKey(),
            'user_id' => $user->getKey(),
        ]);
    }

    public function testEmpty(): void
    {
        $user = User::factory()
            ->has(Role::factory()->count(3))
            ->create();

        $existing = $user->roles()->get();

        $this->repository->update($user)->store([
            'roles' => [],
        ]);

        $this->assertTrue($user->relationLoaded('roles'));
        $this->assertEquals(new EloquentCollection(), $user->getRelation('roles'));

        foreach ($existing as $role) {
            $this->assertDatabaseMissing('role_user', [
                'role_id' => $role->getKey(),
                'user_id' => $user->getKey(),
            ]);
        }
    }

    /**
     * The spec says:
     * "If a given type and id is already in the relationship, the server MUST NOT add it again."
     *
     * This test checks that duplicate ids are not added.
     */
    public function testWithDuplicates(): void
    {
        /** @var User $user */
        $user = User::factory()
            ->has(Role::factory()->count(3))
            ->create();

        $existing = $user->roles()->get();
        $remove = $existing->last();

        $expected = $existing->take(2)->push(
            Role::factory()->create()
        );

        $this->repository->update($user)->store([
            'roles' => collect($expected)->push($expected[1])->map(fn(Role $role) => [
                'type' => 'roles',
                'id' => (string) $role->getRouteKey(),
            ])->all(),
        ]);

        $this->assertTrue($user->relationLoaded('roles'));
        $this->assertInstanceOf(EloquentCollection::class, $actual = $user->getRelation('roles'));
        $this->assertRoles($expected, $actual);

        foreach ($expected as $role) {
            $this->assertDatabaseHas('role_user', [
                'approved' => false,
                'role_id' => $role->getKey(),
                'user_id' => $user->getKey(),
            ]);
        }

        $this->assertDatabaseMissing('role_user', [
            'role_id' => $remove->getKey(),
            'user_id' => $user->getKey(),
        ]);
    }

    public function testWithCount(): void
    {
        $this->actingAs(User::factory()->create(['admin' => true]));

        /** @var User $user */
        $user = User::factory()
            ->has(Role::factory()->count(3))
            ->create();

        $existing = $user->roles()->get();

        $expected = $existing->take(2)->push(
            Role::factory()->create()
        );

        $this->repository->update($user)->withCount('roles')->store([
            'roles' => $expected->map(fn(Role $role) => [
                'type' => 'roles',
                'id' => (string) $role->getRouteKey(),
            ])->all(),
        ]);

        $this->assertEquals(count($expected), $user->roles_count);
    }
}
