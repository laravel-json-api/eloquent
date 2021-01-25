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
use App\Models\Video;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class SyncTest extends TestCase
{

    public function test(): void
    {
        /** @var Tag $tag */
        $tag = Tag::factory()
            ->has(Video::factory()->count(3))
            ->create();

        $existing = $tag->videos()->get();
        $remove = $existing->last();

        $expected = $existing->take(2)->push(
            Video::factory()->create()
        );

        $actual = $this->repository->modifyToMany($tag, 'videos')->sync(
            $expected->map(fn(Video $video) => [
                'type' => 'videos',
                'id' => (string) $video->getRouteKey(),
            ])->all()
        );

        $this->assertInstanceOf(EloquentCollection::class, $actual);
        $this->assertVideos($expected, $actual);

        $this->assertTrue($tag->relationLoaded('videos'));
        $this->assertSame($actual, $tag->getRelation('videos'));

        foreach ($expected as $video) {
            $this->assertDatabaseHas('taggables', [
                'tag_id' => $tag->getKey(),
                'taggable_id' => $video->getKey(),
                'taggable_type' => Video::class,
            ]);
        }

        $this->assertDatabaseMissing('taggables', [
            'tag_id' => $tag->getKey(),
            'taggable_id' => $remove->getKey(),
            'taggable_type' => Video::class,
        ]);
    }

    public function testEmpty(): void
    {
        $tag = Tag::factory()
            ->has(Video::factory()->count(3))
            ->create();

        $existing = $tag->videos()->get();

        $actual = $this->repository
            ->modifyToMany($tag, 'videos')
            ->sync([]);

        $this->assertInstanceOf(EloquentCollection::class, $actual);
        $this->assertEquals(new EloquentCollection(), $actual);
        $this->assertSame(0, $tag->videos()->count());

        $this->assertTrue($tag->relationLoaded('videos'));
        $this->assertSame($actual, $tag->getRelation('videos'));

        foreach ($existing as $video) {
            $this->assertDatabaseMissing('taggables', [
                'tag_id' => $tag->getKey(),
                'taggable_id' => $video->getKey(),
                'taggable_type' => Video::class,
            ]);
        }
    }

    public function testWithIncludePaths(): void
    {
        $tag = Tag::factory()->create();
        $videos = Video::factory()->count(3)->create();

        $ids = $videos->map(fn(Video $video) => [
            'type' => 'videos',
            'id' => (string) $video->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($tag, 'videos')
            ->with('comments')
            ->sync($ids);

        $this->assertCount(3, $actual);
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
        $videos = Video::factory()->count(3)->create();

        $tag->videos()->attach($videos[2]);

        $ids = collect($videos)->push($videos[1])->map(fn(Video $video) => [
            'type' => 'videos',
            'id' => (string) $video->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($tag, 'videos')
            ->sync($ids);

        $this->assertCount(3, $actual);
        $this->assertSame(3, $tag->videos()->count());
        $this->assertVideos($videos, $actual);
    }
}
