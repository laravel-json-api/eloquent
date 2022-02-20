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

use App\Models\Post;
use App\Models\User;
use LaravelJsonApi\Eloquent\Tests\Acceptance\TestCase;

class BelongsToTest extends TestCase
{
    /**
     * @var PostSchema
     */
    private PostSchema $schema;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->schema = $this->app->make(PostSchema::class);

        User::creating(fn(User $user) => $user->password = bcrypt('secret'));
    }

    /**
     * This just tests the functionality of `withDefault()` on a belongs-to relation.
     *
     * @todo remove this, it's just to help me figure out what's going on.
     */
    public function test(): void
    {
        $post = new Post([
            'content' => '...',
            'slug' => 'hello-world',
            'title' => 'Hello World',
        ]);

        $post->save();

        $post->user->fill([
            'name' => 'John',
            'email' => 'j@example.com',
        ])->save();

        $this->assertSame($post->user->getKey(), $post->user_id);
    }

    public function testCreate(): void
    {
        $post = $this->schema->repository()->create()->store($data = [
            'author' => [
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
            ],
            'content' => '...',
            'slug' => 'hello-world',
            'title' => 'Hello World',
        ]);

        $user = $post->user;

        $this->assertDatabaseHas('posts', [
            $post->getKeyName() => $post->getKey(),
            'title' => $data['title'],
            'slug' => $data['slug'],
            'user_id' => $user->getKey(),
        ]);

        $this->assertDatabaseHas('users', [
            $user->getKeyName() => $user->getKey(),
            'name' => $data['author']['name'],
            'email' => $data['author']['email'],
        ]);
    }

    public function testUpdate(): void
    {
        $post = Post::factory()->create();
        $user = $post->user;

        $this->schema->repository()->update($post)->store($data = [
            'slug' => 'hello-world',
            'title' => 'Hello World',
            'author' => [
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
            ],
        ]);

        $this->assertDatabaseHas('posts', [
            $post->getKeyName() => $post->getKey(),
            'title' => $data['title'],
            'slug' => $data['slug'],
            'user_id' => $user->getKey(),
        ]);

        $this->assertDatabaseHas('users', [
            $user->getKeyName() => $user->getKey(),
            'name' => $data['author']['name'],
            'email' => $data['author']['email'],
        ]);
    }
}