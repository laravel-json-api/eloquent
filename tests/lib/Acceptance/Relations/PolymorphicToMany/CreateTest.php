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
use App\Models\User;
use App\Models\Video;

class CreateTest extends TestCase
{

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        Post::creating(function (Post $post) {
            $post->user()->associate(User::factory()->create());
        });
    }

    public function test(): void
    {
        $images = Image::factory()->count(2)->create();
        $videos = Video::factory()->count(2)->create();

        $expected = collect($images)->merge($videos);

        $ids = $expected->map(fn($model) => [
            'type' => ($model instanceof Image) ? 'images' : 'videos',
            'id' => (string) $model->getRouteKey(),
        ])->all();

        $post = $this->repository->create()->store([
            'content' => '...',
            'media' => $ids,
            'slug' => 'hello-world',
            'title' => 'Hello World',
        ]);

        $this->assertTrue($post->relationLoaded('images'));
        $this->assertTrue($post->relationLoaded('videos'));

        $this->assertImages($images, $post->images);
        $this->assertVideos($videos, $post->videos);

        $this->assertDatabaseCount('image_post', count($images));
        $this->assertDatabaseCount('post_video', count($videos));

        foreach ($images as $image) {
            $this->assertDatabaseHas('image_post', [
                'image_id' => $image->getKey(),
                'post_id' => $post->getKey(),
            ]);
        }

        foreach ($videos as $video) {
            $this->assertDatabaseHas('post_video', [
                'post_id' => $post->getKey(),
                'video_uuid' => $video->getKey(),
            ]);
        }
    }

    public function testEmpty(): void
    {
        $post = $this->repository->create()->store([
            'content' => '...',
            'media' => [],
            'slug' => 'hello-world',
            'title' => 'Hello World',
        ]);

        $this->assertTrue($post->relationLoaded('images'));
        $this->assertTrue($post->relationLoaded('videos'));

        $this->assertEmpty($post->images);
        $this->assertEmpty($post->videos);
    }

    public function testWithCount(): void
    {
        $images = Image::factory()->count(2)->create();
        $videos = Video::factory()->count(2)->create();

        $expected = collect($images)->merge($videos);

        $ids = $expected->map(fn($model) => [
            'type' => ($model instanceof Image) ? 'images' : 'videos',
            'id' => (string) $model->getRouteKey(),
        ])->all();

        $post = $this->repository->create()->withCount('media')->store([
            'content' => '...',
            'media' => $ids,
            'slug' => 'hello-world',
            'title' => 'Hello World',
        ]);

        $this->assertEquals(count($images), $post->images_count);
        $this->assertEquals(count($videos), $post->videos_count);
    }
}
