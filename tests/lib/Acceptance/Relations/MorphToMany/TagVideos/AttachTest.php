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

namespace LaravelJsonApi\Eloquent\Tests\Acceptance\Relations\MorphToMany\TagVideos;

use App\Models\Tag;
use App\Models\User;
use App\Models\Video;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class AttachTest extends TestCase
{

    public function test(): void
    {
        $this->actingAs(User::factory()->create(['admin' => true]));

        $tag = Tag::factory()
            ->hasAttached(Video::factory()->count(3), ['approved' => false])
            ->create();

        /** We force the relation to be loaded before the change, so that we can test it is unset. */
        $existing = clone $tag->videos;
        $expected = Video::factory()->count(2)->create();

        $ids = $expected->map(fn(Video $video) => [
            'type' => 'videos',
            'id' => (string) $video->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($tag, 'videos')
            ->attach($ids);

        $this->assertInstanceOf(EloquentCollection::class, $actual);
        $this->assertVideos($expected, $actual);
        $this->assertSame(5, $tag->videos()->count());

        /**
         * We expect the relation to be unloaded because we know it has changed in the
         * database, but we don't know what it now is in its entirety.
         */
        $this->assertFalse($tag->relationLoaded('videos'));

        foreach ($existing as $video) {
            $this->assertDatabaseHas('taggables', [
                'approved' => false, // we expect existing to keep their value
                'tag_id' => $tag->getKey(),
                'taggable_id' => $video->getKey(),
                'taggable_type' => Video::class,
            ]);
        }

        foreach ($expected as $video) {
            $this->assertDatabaseHas('taggables', [
                'approved' => true, // newly added should have the calculated value.
                'tag_id' => $tag->getKey(),
                'taggable_id' => $video->getKey(),
                'taggable_type' => Video::class,
            ]);
        }
    }

    public function testWithIncludePaths(): void
    {
        $tag = Tag::factory()->create();
        $videos = Video::factory()->count(2)->create();

        $ids = $videos->map(fn(Video $video) => [
            'type' => 'videos',
            'id' => (string) $video->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($tag, 'videos')
            ->with('comments')
            ->attach($ids);

        $this->assertVideos($videos, $actual);
        $this->assertTrue($actual->every(fn(Video $video) => $video->relationLoaded('comments')));
    }


    /**
     * The spec says:
     * "If a given type and id is already in the relationship, the server MUST NOT add it again."
     *
     * This test checks that duplicate ids are not added.
     */
    public function testWithDuplicates(): void
    {
        /** @var Tag $tag */
        $tag = Tag::factory()->create();
        $videos = Video::factory()->count(2)->create();

        $tag->videos()->attach($videos[1]);

        $ids = collect($videos)->push($videos[0], $videos[1])->map(fn(Video $video) => [
            'type' => 'videos',
            'id' => (string) $video->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($tag, 'videos')
            ->attach($ids);

        $this->assertVideos($videos, $actual);
        $this->assertSame(2, $tag->videos()->count());

        foreach ($videos as $video) {
            $this->assertDatabaseHas('taggables', [
                'approved' => false,
                'tag_id' => $tag->getKey(),
                'taggable_id' => $video->getKey(),
                'taggable_type' => Video::class,
            ]);
        }
    }
}
