<?php
/*
 * Copyright 2023 Cloud Creativity Limited
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

namespace LaravelJsonApi\Eloquent\Tests\Acceptance\Relations\PolymorphicToMany;

use App\Models\Image;
use App\Models\Post;
use App\Models\Video;
use App\Schemas\VideoSchema;
use Illuminate\Database\Eloquent\Model;

class QueryTest extends TestCase
{

    public function test(): void
    {
        $post = Post::factory()
            ->has(Image::factory()->count(3))
            ->has(Video::factory()->count(2))
            ->create();

        $expected = $post->images()->get()->merge(
            $post->videos()->get()
        );

        $actual = $this->repository
            ->queryToMany($post, 'media')
            ->cursor();

        $this->assertCount(5, $actual);
        $this->assertMedia($expected, $actual);

        // as the relationship is countable, we expect the count to be loaded so the relationship meta is complete.
        $this->assertEquals(3, $post->images_count);
        $this->assertEquals(2, $post->videos_count);
    }

    public function testWithIncludePaths(): void
    {
        $post = Post::factory()
            ->has(Image::factory()->count(3))
            ->has(Video::factory()->count(3))
            ->create();

        $expected = $post->images()->get()->merge(
            $post->videos()->get()
        );

        $actual = $this->repository
            ->queryToMany($post, 'media')
            ->with(['imageable', 'comments'])
            ->get();

        $this->assertCount(6, $actual);
        $this->assertMedia($expected, $actual);

        $this->assertTrue(
            $actual->whereInstanceOf(Image::class)->every(fn(Image $image) => $image->relationLoaded('imageable')),
            'images'
        );

        $this->assertTrue(
            $actual->whereInstanceOf(Video::class)->every(fn(Video $video) => $video->relationLoaded('comments')),
            'videos'
        );
    }

    public function testDefaultEagerLoading(): void
    {
        $this->createSchemaWithDefaultEagerLoading(VideoSchema::class, 'comments');

        $post = Post::factory()
            ->has(Image::factory()->count(3))
            ->has(Video::factory()->count(3))
            ->create();

        $expected = $post->images()->get()->merge(
            $post->videos()->get()
        );

        $actual = $this->repository
            ->queryToMany($post, 'media')
            ->get();

        $this->assertCount(6, $actual);
        $this->assertMedia($expected, $actual);

        $this->assertTrue(
            $actual->whereInstanceOf(Video::class)->every(fn(Video $video) => $video->relationLoaded('comments')),
            'videos'
        );
    }

    public function testDefaultEagerLoadingAndIncludePaths(): void
    {
        $this->createSchemaWithDefaultEagerLoading(VideoSchema::class, 'comments');

        $post = Post::factory()
            ->has(Image::factory()->count(3))
            ->has(Video::factory()->count(3))
            ->create();

        $expected = $post->images()->get()->merge(
            $post->videos()->get()
        );

        $actual = $this->repository
            ->queryToMany($post, 'media')
            ->with(['imageable'])
            ->get();

        $this->assertCount(6, $actual);
        $this->assertMedia($expected, $actual);

        $this->assertTrue(
            $actual->whereInstanceOf(Image::class)->every(fn(Image $image) => $image->relationLoaded('imageable')),
            'images'
        );

        $this->assertTrue(
            $actual->whereInstanceOf(Video::class)->every(fn(Video $video) => $video->relationLoaded('comments')),
            'videos'
        );
    }

    public function testWithFilter(): void
    {
        $post = Post::factory()
            ->has(Image::factory()->count(3))
            ->has(Video::factory()->count(3))
            ->create();

        $expected = $post->images()->take(2)->get()->merge(
            $post->videos()->take(1)->get()
        );

        $ids = $expected
            ->map(fn(Model $model) => (string) $model->getRouteKey())
            ->all();

        $actual = $this->repository
            ->queryToMany($post, 'media')
            ->filter(['id' => $ids])
            ->get();

        $this->assertCount(3, $actual);
        $this->assertMedia($expected, $actual);
    }

    public function testWithSort(): void
    {
        $post = Post::factory()
            ->has(Image::factory()->count(3))
            ->has(Video::factory()->count(3))
            ->create();

        $expected = $post->images()->get()->sortByDesc('id')->values()->merge(
            $post->videos()->get()->sortByDesc('uuid')->values()
        );

        $actual = $this->repository
            ->queryToMany($post, 'media')
            ->sort('-id')
            ->cursor();

        $this->assertCount(6, $actual);
        $this->assertMedia($expected, $actual);
    }
}
