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

    public function testWithCount(): void
    {
        $roles = Role::factory()->count(2)->create();

        $this->actingAs(User::factory()->create(['admin' => true]));

        $user = $this->repository->create()->withCount('roles')->store([
            'email' => 'john.doe@example.com',
            'name' => 'John Doe',
            'roles' => $roles->map(fn(Role $role) => [
                'type' => 'roles',
                'id' => (string) $role->getRouteKey(),
            ])->all(),
        ]);

        $this->assertEquals(count($roles), $user->roles_count);
    }
}
