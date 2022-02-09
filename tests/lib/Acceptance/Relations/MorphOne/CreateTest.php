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

namespace LaravelJsonApi\Eloquent\Tests\Acceptance\Relations\MorphOne;

use App\Models\Image;
use App\Models\Post;
use App\Models\User;

class CreateTest extends TestCase
{

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        Post::creating(fn(Post $post) => $post->user()->associate(
            User::factory()->create()
        ));
    }

    public function test(): void
    {
        $image = Image::factory()->create();

        $post = $this->repository->create()->store([
            'content' => '...',
            'image' => [
                'type' => 'images',
                'id' => (string) $image->getRouteKey(),
            ],
            'slug' => 'hello-world',
            'title' => 'Hello World!',
        ]);

        $this->assertTrue($post->relationLoaded('image'));
        $this->assertTrue($image->is($post->getRelation('image')));

        $this->assertDatabaseHas('images', [
            'id' => $image->getKey(),
            'imageable_id' => $post->getKey(),
            'imageable_type' => Post::class,
        ]);
    }

    public function testNull(): void
    {
        $post = $this->repository->create()->store([
            'content' => '...',
            'image' => null,
            'slug' => 'hello-world',
            'title' => 'Hello World!',
        ]);

        $this->assertTrue($post->relationLoaded('image'));
        $this->assertNull($post->getRelation('image'));
    }
}
