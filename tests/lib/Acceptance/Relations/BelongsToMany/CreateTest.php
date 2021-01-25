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

namespace LaravelJsonApi\Eloquent\Tests\Acceptance\Relations\BelongsToMany;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class CreateTest extends TestCase
{

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        User::creating(fn(User $user) => $user->password = 'secret');
    }

    public function test(): void
    {
        $roles = Role::factory()->count(2)->create();

        $this->actingAs(User::factory()->create(['admin' => true]));

        $user = $this->repository->create()->store([
            'email' => 'john.doe@example.com',
            'name' => 'John Doe',
            'roles' => $roles->map(fn(Role $role) => [
                'type' => 'roles',
                'id' => (string) $role->getRouteKey(),
            ])->all(),
        ]);

        $this->assertTrue($user->relationLoaded('roles'));
        $this->assertInstanceOf(EloquentCollection::class, $actual = $user->getRelation('roles'));
        $this->assertRoles($roles, $actual);

        foreach ($roles as $role) {
            $this->assertDatabaseHas('role_user', [
                'approved' => true,
                'role_id' => $role->getKey(),
                'user_id' => $user->getKey(),
            ]);
        }
    }

    public function testEmpty(): void
    {
        $user = $this->repository->create()->store([
            'email' => 'john.doe@example.com',
            'name' => 'John Doe',
            'roles' => [],
        ]);

        $this->assertTrue($user->relationLoaded('roles'));
        $this->assertEquals(new EloquentCollection(), $user->getRelation('roles'));
    }


    /**
     * The spec says:
     * "If a given type and id is already in the relationship, the server MUST NOT add it again."
     *
     * This test checks that duplicate ids are not added.
     */
    public function testWithDuplicates(): void
    {
        $roles = Role::factory()->count(2)->create();

        $user = $this->repository->create()->store([
            'email' => 'john.doe@example.com',
            'name' => 'John Doe',
            'roles' => collect($roles)->push($roles[1])->map(fn(Role $role) => [
                'type' => 'roles',
                'id' => (string) $role->getRouteKey(),
            ])->all(),
        ]);

        $this->assertTrue($user->relationLoaded('roles'));
        $this->assertInstanceOf(EloquentCollection::class, $actual = $user->getRelation('roles'));
        $this->assertRoles($roles, $actual);
        $this->assertSame(2, $user->roles()->count());

        foreach ($roles as $role) {
            $this->assertDatabaseHas('role_user', [
                'role_id' => $role->getKey(),
                'user_id' => $user->getKey(),
                'approved' => false,
            ]);
        }
    }
}
