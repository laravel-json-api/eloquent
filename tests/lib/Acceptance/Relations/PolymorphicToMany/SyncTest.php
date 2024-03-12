<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Eloquent\Tests\Acceptance\Relations\PolymorphicToMany;

use App\Models\Image;
use App\Models\Post;
use App\Models\Video;
use LaravelJsonApi\Eloquent\Polymorphism\MorphMany;

class SyncTest extends TestCase
{

    public function test(): void
    {
        $post = Post::factory()
            ->has(Image::factory()->count(3))
            ->has(Video::factory()->count(3))
            ->create();

        $existingImages = $post->images()->get();
        $existingVideos = $post->videos()->get();

        $removeImage = $existingImages->last();
        $removeVideo = $existingVideos->last();

        $expectedImages = $existingImages->take(2)->push(Image::factory()->create());
        $expectedVideos = $existingVideos->take(2)->push(Video::factory()->create());

        $expected = collect($expectedImages)->merge($expectedVideos);

        $ids = $expected->map(fn($model) => [
            'type' => ($model instanceof Image) ? 'images' : 'videos',
            'id' => (string) $model->getRouteKey(),
        ])->all();

        $actual = $this->repository->modifyToMany($post, 'media')->sync($ids);

        $this->assertTrue($post->relationLoaded('images'));
        $this->assertTrue($post->relationLoaded('videos'));

        $this->assertImages($expectedImages, $post->images);
        $this->assertVideos($expectedVideos, $post->videos);

        $this->assertInstanceOf(MorphMany::class, $actual);
        $this->assertMedia($expected, $actual);

        // as the relationship is countable, we expect the count to be loaded so the relationship meta is complete.
        $this->assertEquals(count($expectedImages), $post->images_count);
        $this->assertEquals(count($expectedVideos), $post->videos_count);

        $this->assertDatabaseCount('image_post', count($expectedImages));
        $this->assertDatabaseCount('post_video', count($expectedVideos));

        foreach ($expectedImages as $image) {
            $this->assertDatabaseHas('image_post', [
                'image_id' => $image->getKey(),
                'post_id' => $post->getKey(),
            ]);
        }

        $this->assertDatabaseMissing('image_post', [
            'image_id' => $removeImage->getKey(),
            'post_id' => $post->getKey(),
        ]);

        foreach ($expectedVideos as $video) {
            $this->assertDatabaseHas('post_video', [
                'post_id' => $post->getKey(),
                'video_uuid' => $video->getKey(),
            ]);
        }

        $this->assertDatabaseMissing('post_video', [
            'post_id' => $post->getKey(),
            'video_uuid' => $removeVideo->getKey(),
        ]);
    }

    public function testEmpty(): void
    {
        $post = Post::factory()
            ->has(Image::factory()->count(3))
            ->has(Video::factory()->count(3))
            ->create();

        $actual = $this->repository
            ->modifyToMany($post, 'media')
            ->sync([]);

        $this->assertCount(0, $actual);

        $this->assertTrue($post->relationLoaded('images'));
        $this->assertCount(0, $post->images);

        $this->assertTrue($post->relationLoaded('videos'));
        $this->assertCount(0, $post->videos);

        $this->assertDatabaseCount('image_post', 0);
        $this->assertDatabaseCount('post_video', 0);
    }

    public function testWithIncludePaths(): void
    {
        $post = Post::factory()
            ->has(Image::factory()->count(3))
            ->has(Video::factory()->count(3))
            ->create();

        $existingImages = $post->images()->get();
        $existingVideos = $post->videos()->get();

        $expectedImages = $existingImages->take(2)->push(Image::factory()->create());
        $expectedVideos = $existingVideos->take(2)->push(Video::factory()->create());

        $expected = collect($expectedImages)->merge($expectedVideos);

        $ids = $expected->map(fn($model) => [
            'type' => ($model instanceof Image) ? 'images' : 'videos',
            'id' => (string) $model->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($post, 'media')
            ->with(['imageable', 'comments'])
            ->sync($ids);

        $this->assertImages($expectedImages, $post->images);
        $this->assertVideos($expectedVideos, $post->videos);
        $this->assertMedia($expected, $actual);

        $this->assertTrue(collect($actual)
            ->whereInstanceOf(Image::class)
            ->every(fn(Image $image) => $image->relationLoaded('imageable')));

        $this->assertTrue(collect($actual)
            ->whereInstanceOf(Video::class)
            ->every(fn(Video $video) => $video->relationLoaded('comments')));
    }
}
