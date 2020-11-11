<?php
/*
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
}
