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

namespace LaravelJsonApi\Eloquent\Tests\Acceptance\Relations\MorphToMany\TagVideos;

use App\Models\Tag;
use App\Models\Video;
use App\Schemas\VideoSchema;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class DetachTest extends TestCase
{

    public function test(): void
    {
        $tag = Tag::factory()
            ->has(Video::factory()->count(3))
            ->create();

        /** We force the relation to be loaded before the change, so that we can test it is unset. */
        $existing = clone $tag->videos;
        $remove = $existing->take(2);
        $keep = $existing->last();

        $ids = $remove->map(fn(Video $video) => [
            'type' => 'videos',
            'id' => (string) $video->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($tag, 'videos')
            ->detach($ids);

        $this->assertInstanceOf(EloquentCollection::class, $actual);
        $this->assertVideos($remove, $actual);
        $this->assertSame(1, $tag->videos()->count());

        // as the relationship is countable, we expect the count to be loaded so the relationship meta is complete.
        $this->assertEquals(1, $tag->videos_count);

        /**
         * We expect the relation to be unloaded because we know it has changed in the
         * database, but we don't know what it now is in its entirety.
         */
        $this->assertFalse($tag->relationLoaded('tags'));

        $this->assertDatabaseHas('taggables', [
            'tag_id' => $tag->getKey(),
            'taggable_id' => $keep->getKey(),
            'taggable_type' => Video::class,
        ]);

        foreach ($remove as $video) {
            $this->assertDatabaseMissing('taggables', [
                'tag_id' => $tag->getKey(),
                'taggable_id' => $video->getKey(),
                'taggable_type' => Video::class,
            ]);
        }
    }

    public function testWithIncludePaths(): void
    {
        $tag = Tag::factory()
            ->has(Video::factory()->count(3))
            ->create();

        $roles = clone $tag->videos;

        $ids = $roles->map(fn(Video $video) => [
            'type' => 'videos',
            'id' => (string) $video->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($tag, 'videos')
            ->with('comments')
            ->detach($ids);

        $this->assertVideos($roles, $actual);
        $this->assertTrue($actual->every(fn(Video $video) => $video->relationLoaded('comments')));
    }

    public function testWithDefaultEagerLoading(): void
    {
        $this->createSchemaWithDefaultEagerLoading(VideoSchema::class, 'comments');

        $tag = Tag::factory()
            ->has(Video::factory()->count(3))
            ->create();

        $roles = clone $tag->videos;

        $ids = $roles->map(fn(Video $video) => [
            'type' => 'videos',
            'id' => (string) $video->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($tag, 'videos')
            ->detach($ids);

        $this->assertVideos($roles, $actual);
        $this->assertTrue($actual->every(fn(Video $video) => $video->relationLoaded('comments')));
    }
}
