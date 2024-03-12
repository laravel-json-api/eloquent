<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
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
