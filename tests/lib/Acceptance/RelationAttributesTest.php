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

use App\Models\User;
use App\Schemas\UserSchema;

class RelationAttributesTest extends TestCase
{

    /**
     * @var UserSchema
     */
    private UserSchema $schema;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->schema = $this->app->make(UserSchema::class);

        User::creating(fn(User $user) => $user->password = bcrypt('secret'));
    }

    public function testCreate(): void
    {
        $user = $this->schema->repository()->create()->store($data = [
            'email' => 'john.doe@example.com',
            'name' => 'John Doe',
            'profile' => [
                'description' => 'My name is John Doe',
                'image' => 'http://localhost/images/john-doe.jpg',
            ],
        ]);

        $this->assertDatabaseHas('users', [
            $user->getKeyName() => $user->getKey(),
            'email' => $data['email'],
            'name' => $data['name'],
        ]);

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->getKey(),
            'description' => $data['profile']['description'],
            'image' => $data['profile']['image'],
        ]);
    }

    public function testUpdate(): void
    {
        $user = User::factory()->create();

        $user = $this->schema->repository()->update($user)->store($data = [
            'email' => 'john.doe@example.com',
            'name' => 'John Doe',
            'profile' => [
                'description' => 'My name is John Doe',
                'image' => 'http://localhost/images/john-doe.jpg',
            ],
        ]);

        $this->assertDatabaseHas('users', [
            $user->getKeyName() => $user->getKey(),
            'email' => $data['email'],
            'name' => $data['name'],
        ]);

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->getKey(),
            'description' => $data['profile']['description'],
            'image' => $data['profile']['image'],
        ]);
    }

    public function testUpdateOnlyProfile(): void
    {
        $user = User::factory()->create();
        $user->profile->update([
            'description' => 'unexpected',
            'image' => 'http://some/other',
        ]);

        $this->schema->repository()->update($user)->store($data = [
            'profile' => [
                'description' => 'My name is John Doe',
                'image' => 'http://localhost/images/john-doe.jpg',
            ],
        ]);

        $this->assertDatabaseHas('users', $user->getRawOriginal());

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->getKey(),
            'description' => $data['profile']['description'],
            'image' => $data['profile']['image'],
        ]);
    }
}
