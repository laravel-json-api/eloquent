<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Eloquent\Tests\Acceptance\Relations\MorphTo;

use App\Models\Image;
use App\Models\Post;
use App\Models\User;

class UpdateTest extends TestCase
{

    public function testNullToUser(): void
    {
        $image = Image::factory()->create([
            'imageable_id' => null,
            'imageable_type' => null,
        ]);

        $user = User::factory()->create();

        $this->repository->update($image)->store([
            'imageable' => [
                'type' => 'users',
                'id' => (string) $user->getRouteKey(),
            ],
        ]);

        $this->assertTrue($image->relationLoaded('imageable'));
        $this->assertTrue($user->is($image->getRelation('imageable')));

        $this->assertDatabaseHas('images', [
            'id' => $image->getKey(),
            'imageable_id' => $user->getKey(),
            'imageable_type' => User::class,
        ]);
    }

    public function testUserToNull(): void
    {
        $image = Image::factory()
            ->for(User::factory(), 'imageable')
            ->create();

        $this->repository->update($image)->store([
            'imageable' => null,
        ]);

        $this->assertTrue($image->relationLoaded('imageable'));
        $this->assertNull($image->getRelation('imageable'));

        $this->assertDatabaseHas('images', [
            'id' => $image->getKey(),
            'imageable_id' => null,
            'imageable_type' => null,
        ]);
    }

    public function testUserToPost(): void
    {
        $image = Image::factory()
            ->for(User::factory(), 'imageable')
            ->create();

        $post = Post::factory()->create();

        $this->repository->update($image)->store([
            'imageable' => [
                'type' => 'posts',
                'id' => (string) $post->getRouteKey(),
            ],
        ]);

        $this->assertTrue($image->relationLoaded('imageable'));
        $this->assertTrue($post->is($image->getRelation('imageable')));

        $this->assertDatabaseHas('images', [
            'id' => $image->getKey(),
            'imageable_id' => $post->getKey(),
            'imageable_type' => Post::class,
        ]);
    }

}
