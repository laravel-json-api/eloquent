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

class DetachTest extends TestCase
{

    public function test(): void
    {
        $post = Post::factory()
            ->has(Image::factory()->count(3))
            ->has(Video::factory()->count(3))
            ->create();

        $existingImages = $post->images()->get();
        $existingVideos = $post->videos()->get();

        $removeImages = $existingImages->take(2);
        $removeVideos = $existingVideos->take(1);

        $keepImages = collect([$existingImages->last()]);
        $keepVideos = $existingVideos->skip(1);

        $detach = collect($removeImages)->merge($removeVideos);

        $ids = $detach->map(fn($model) => [
            'type' => ($model instanceof Image) ? 'images' : 'videos',
            'id' => (string) $model->getRouteKey(),
        ])->all();

        $actual = $this->repository->modifyToMany($post, 'media')->detach($ids);

        /**
         * We expect the relation to be unloaded because we know it has changed in the
         * database, but we don't know what it now is in its entirety.
         */
        $this->assertFalse($post->relationLoaded('images'));
        $this->assertFalse($post->relationLoaded('videos'));

        $this->assertInstanceOf(MorphMany::class, $actual);
        $this->assertMedia($detach, $actual);

        // as the relationship is countable, we expect the count to be loaded so the relationship meta is complete.
        $this->assertEquals(count($existingImages) - count($removeImages), $post->images_count);
        $this->assertEquals(count($existingVideos) - count($removeVideos), $post->videos_count);

        $this->assertDatabaseCount('image_post', count($existingImages) - count($removeImages));
        $this->assertDatabaseCount('post_video', count($existingVideos) - count($removeVideos));

        foreach ($keepImages as $image) {
            $this->assertDatabaseHas('image_post', [
                'image_id' => $image->getKey(),
                'post_id' => $post->getKey(),
            ]);
        }

        foreach ($removeImages as $image) {
            $this->assertDatabaseMissing('image_post', [
                'image_id' => $image->getKey(),
                'post_id' => $post->getKey(),
            ]);
        }

        foreach ($keepVideos as $video) {
            $this->assertDatabaseHas('post_video', [
                'post_id' => $post->getKey(),
                'video_uuid' => $video->getKey(),
            ]);
        }

        foreach ($removeVideos as $video) {
            $this->assertDatabaseMissing('post_video', [
                'post_id' => $post->getKey(),
                'video_uuid' => $video->getKey(),
            ]);
        }
    }

    public function testWithIncludePaths(): void
    {
        $post = Post::factory()
            ->has(Image::factory()->count(3))
            ->has(Video::factory()->count(3))
            ->create();

        $existingImages = $post->images()->get();
        $existingVideos = $post->videos()->get();

        $removeImages = $existingImages->take(2);
        $removeVideos = $existingVideos->take(1);

        $detach = collect($removeImages)->merge($removeVideos);

        $ids = $detach->map(fn($model) => [
            'type' => ($model instanceof Image) ? 'images' : 'videos',
            'id' => (string) $model->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($post, 'media')
            ->with(['imageable', 'comments'])
            ->detach($ids);

        $this->assertMedia($detach, $actual);

        $this->assertTrue(collect($actual)
            ->whereInstanceOf(Image::class)
            ->every(fn(Image $image) => $image->relationLoaded('imageable')));

        $this->assertTrue(collect($actual)
            ->whereInstanceOf(Video::class)
            ->every(fn(Video $video) => $video->relationLoaded('comments')));
    }
}
