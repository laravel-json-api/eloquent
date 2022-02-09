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

namespace LaravelJsonApi\Eloquent\Tests\Acceptance\Relations\PolymorphicToMany;

use App\Models\Image;
use App\Models\Post;
use App\Models\Video;
use LaravelJsonApi\Eloquent\Polymorphism\MorphMany;

class AttachTest extends TestCase
{

    public function test(): void
    {
        $post = Post::factory()
            ->has(Image::factory()->count(1))
            ->has(Video::factory()->count(1))
            ->create();

        $add = collect([
            Image::factory()->create(),
            Video::factory()->create(),
        ]);

        $expectedImages = collect([
            $post->images()->first(),
            $add[0],
        ]);

        $expectedVideos = collect([
            $post->videos()->first(),
            $add[1],
        ]);

        $ids = $add->map(fn($model) => [
            'type' => ($model instanceof Image) ? 'images' : 'videos',
            'id' => (string) $model->getRouteKey(),
        ])->all();

        $actual = $this->repository->modifyToMany($post, 'media')->attach($ids);

        /**
         * We expect the relation to be unloaded because we know it has changed in the
         * database, but we don't know what it now is in its entirety.
         */
        $this->assertFalse($post->relationLoaded('images'));
        $this->assertFalse($post->relationLoaded('videos'));

        $this->assertInstanceOf(MorphMany::class, $actual);
        $this->assertMedia($add, $actual);

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

        foreach ($expectedVideos as $video) {
            $this->assertDatabaseHas('post_video', [
                'post_id' => $post->getKey(),
                'video_uuid' => $video->getKey(),
            ]);
        }
    }

    public function testWithIncludePaths(): void
    {
        $post = Post::factory()
            ->has(Image::factory()->count(1))
            ->has(Video::factory()->count(1))
            ->create();

        $add = collect([
            Image::factory()->create(),
            Video::factory()->create(),
        ]);

        $ids = $add->map(fn($model) => [
            'type' => ($model instanceof Image) ? 'images' : 'videos',
            'id' => (string) $model->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($post, 'media')
            ->with(['imageable', 'comments'])
            ->attach($ids);

        $this->assertMedia($add, $actual);

        $this->assertTrue(collect($actual)
            ->whereInstanceOf(Image::class)
            ->every(fn(Image $image) => $image->relationLoaded('imageable')));

        $this->assertTrue(collect($actual)
            ->whereInstanceOf(Video::class)
            ->every(fn(Video $video) => $video->relationLoaded('comments')));
    }
}
