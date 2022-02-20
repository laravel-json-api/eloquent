<?php
/*
 * Copyright 2022 Cloud Creativity Limited
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

namespace LaravelJsonApi\Eloquent\Tests\Acceptance\RelationAttributes;

use App\Models\User;
use App\Schemas\UserSchema;
use LaravelJsonApi\Eloquent\Tests\Acceptance\TestCase;

class HasOneTest extends TestCase
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

        $this->schema->repository()->update($user)->store($data = [
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
