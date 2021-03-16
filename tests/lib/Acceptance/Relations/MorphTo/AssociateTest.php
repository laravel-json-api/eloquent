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

namespace LaravelJsonApi\Eloquent\Tests\Acceptance\Relations\MorphTo;

use App\Models\Image;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Schemas\PostSchema;

class AssociateTest extends TestCase
{

    public function testNullToUser(): void
    {
        $image = Image::factory()->create([
            'imageable_id' => null,
            'imageable_type' => null,
        ]);

        $user = User::factory()->create();

        $actual = $this->repository->modifyToOne($image, 'imageable')->associate([
            'type' => 'users',
            'id' => (string) $user->getRouteKey(),
        ]);

        $this->assertTrue($user->is($actual));
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

        $actual = $this->repository->modifyToOne($image, 'imageable')->associate(null);

        $this->assertNull($actual);
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

        $actual = $this->repository->modifyToOne($image, 'imageable')->associate([
            'type' => 'posts',
            'id' => (string) $post->getRouteKey(),
        ]);

        $this->assertTrue($post->is($actual));
        $this->assertTrue($image->relationLoaded('imageable'));
        $this->assertTrue($post->is($image->getRelation('imageable')));

        $this->assertDatabaseHas('images', [
            'id' => $image->getKey(),
            'imageable_id' => $post->getKey(),
            'imageable_type' => Post::class,
        ]);
    }

    public function testWithIncludePaths(): void
    {
        $image = Image::factory()->create();
        $post = Post::factory()->create();

        $actual = $this->repository->modifyToOne($image, 'imageable')->with('author,roles')->associate([
            'type' => 'posts',
            'id' => (string) $post->getRouteKey(),
        ]);

        $this->assertTrue($post->is($actual));
        $this->assertTrue($actual->relationLoaded('user'));
        $this->assertTrue($post->user->is($actual->getRelation('user')));
    }

    public function testWithDefaultEagerLoading(): void
    {
        $this->createSchemaWithDefaultEagerLoading(PostSchema::class, 'user');

        $image = Image::factory()->create();
        $post = Post::factory()->create();

        $actual = $this->repository->modifyToOne($image, 'imageable')->associate([
            'type' => 'posts',
            'id' => (string) $post->getRouteKey(),
        ]);

        $this->assertTrue($post->is($actual));
        $this->assertTrue($actual->relationLoaded('user'));
        $this->assertTrue($post->user->is($actual->getRelation('user')));
    }

    public function testWithCount(): void
    {
        $image = Image::factory()->create();

        $post = Post::factory()
            ->has(Tag::factory()->count(2))
            ->create();

        $actual = $this->repository->modifyToOne($image, 'imageable')->withCount('tags,roles')->associate([
            'type' => 'posts',
            'id' => (string) $post->getRouteKey(),
        ]);

        $this->assertTrue($post->is($actual));
        $this->assertEquals(2, $actual->tags_count);
    }
}
