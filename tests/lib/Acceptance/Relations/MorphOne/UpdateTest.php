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

namespace LaravelJsonApi\Eloquent\Tests\Acceptance\Relations\MorphOne;

use App\Models\Image;
use App\Models\Post;

class UpdateTest extends TestCase
{

    public function testNullToImage(): void
    {
        $post = Post::factory()->create();
        $image = Image::factory()->create(['imageable_id' => null, 'imageable_type' => null]);

        $this->repository->update($post)->store([
            'image' => [
                'type' => 'images',
                'id' => (string) $image->getRouteKey(),
            ],
        ]);

        $this->assertTrue($post->relationLoaded('image'));
        $this->assertTrue($image->is($post->getRelation('image')));

        $this->assertDatabaseHas('images', [
            'id' => $image->getKey(),
            'imageable_id' => $post->getKey(),
            'imageable_type' => Post::class,
        ]);
    }

    public function testImageToNullKeepsImage(): void
    {
        $post = Post::factory()->create();
        $image = Image::factory()->create([
            'imageable_id' => $post->getKey(),
            'imageable_type' => Post::class,
        ]);

        $this->repository->update($post)->store([
            'image' => null,
        ]);

        $this->assertTrue($post->relationLoaded('image'));
        $this->assertNull($post->getRelation('image'));

        $this->assertDatabaseHas('images', [
            'id' => $image->getKey(),
            'imageable_id' => null,
            'imageable_type' => null,
        ]);
    }

    public function testImageToNullDeletesImage(): void
    {
        $this->schema->relationship('image')->forceDeleteDetachedModel();

        $post = Post::factory()->create();
        $image = Image::factory()->create([
            'imageable_id' => $post->getKey(),
            'imageable_type' => Post::class,
        ]);

        $this->repository->update($post)->store([
            'image' => null,
        ]);

        $this->assertTrue($post->relationLoaded('image'));
        $this->assertNull($post->getRelation('image'));

        $this->assertDatabaseMissing('images', [
            $image->getKeyName() => $image->getKey(),
        ]);
    }

    public function testImageToImageKeepsOriginalImage(): void
    {
        $image1 = Image::factory()
            ->for(Post::factory(), 'imageable')
            ->create();

        $image2 = Image::factory()
            ->for(Post::factory(), 'imageable')
            ->create();

        $post = $image1->imageable;

        $this->repository->update($post)->store([
            'image' => [
                'type' => 'images',
                'id' => (string) $image2->getRouteKey(),
            ],
        ]);

        $this->assertTrue($post->relationLoaded('image'));
        $this->assertTrue($image2->is($post->getRelation('image')));

        $this->assertDatabaseHas('images', [
            'id' => $image2->getKey(),
            'imageable_id' => $post->getKey(),
            'imageable_type' => Post::class,
        ]);

        $this->assertDatabaseHas('images', [
            'id' => $image1->getKey(),
            'imageable_id' => null,
            'imageable_type' => null,
        ]);
    }

    public function testImageToImageDeletesOriginalImage(): void
    {
        $this->schema->relationship('image')->forceDeleteDetachedModel();

        $image1 = Image::factory()
            ->for(Post::factory(), 'imageable')
            ->create();

        $image2 = Image::factory()
            ->for(Post::factory(), 'imageable')
            ->create();

        $post = $image1->imageable;

        $this->repository->update($post)->store([
            'image' => [
                'type' => 'images',
                'id' => (string) $image2->getRouteKey(),
            ],
        ]);

        $this->assertTrue($post->relationLoaded('image'));
        $this->assertTrue($image2->is($post->getRelation('image')));

        $this->assertDatabaseHas('images', [
            'id' => $image2->getKey(),
            'imageable_id' => $post->getKey(),
            'imageable_type' => Post::class,
        ]);

        $this->assertDatabaseMissing('images', [
            $image1->getKeyName() => $image1->getKey(),
        ]);
    }

}
