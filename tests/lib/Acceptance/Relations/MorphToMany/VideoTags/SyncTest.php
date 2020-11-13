<?php
/*
 * Copyright 2020 Cloud Creativity Limited
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

namespace LaravelJsonApi\Eloquent\Tests\Acceptance\Relations\MorphToMany\VideoTags;

use App\Models\Tag;
use App\Models\Video;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class SyncTest extends TestCase
{

    public function test(): void
    {
        /** @var Video $video */
        $video = Video::factory()
            ->has(Tag::factory()->count(3))
            ->create();

        $existing = $video->tags()->get();
        $remove = $existing->last();

        $expected = $existing->take(2)->push(
            Tag::factory()->create()
        );

        $actual = $this->repository->modifyToMany($video, 'tags')->sync(
            $expected->map(fn(Tag $tag) => [
                'type' => 'tags',
                'id' => (string) $tag->getRouteKey(),
            ])->all()
        );

        $this->assertInstanceOf(EloquentCollection::class, $actual);
        $this->assertTags($expected, $actual);

        $this->assertTrue($video->relationLoaded('tags'));
        $this->assertSame($actual, $video->getRelation('tags'));

        foreach ($expected as $tag) {
            $this->assertDatabaseHas('taggables', [
                'tag_id' => $tag->getKey(),
                'taggable_id' => $video->getKey(),
                'taggable_type' => Video::class,
            ]);
        }

        $this->assertDatabaseMissing('taggables', [
            'tag_id' => $remove->getKey(),
            'taggable_id' => $video->getKey(),
            'taggable_type' => Video::class,
        ]);
    }

    public function testEmpty(): void
    {
        $video = Video::factory()
            ->has(Tag::factory()->count(3))
            ->create();

        $existing = $video->tags()->get();

        $actual = $this->repository
            ->modifyToMany($video, 'tags')
            ->sync([]);

        $this->assertInstanceOf(EloquentCollection::class, $actual);
        $this->assertEquals(new EloquentCollection(), $actual);
        $this->assertSame(0, $video->tags()->count());

        $this->assertTrue($video->relationLoaded('tags'));
        $this->assertSame($actual, $video->getRelation('tags'));

        foreach ($existing as $tag) {
            $this->assertDatabaseMissing('taggables', [
                'tag_id' => $tag->getKey(),
                'taggable_id' => $video->getKey(),
                'taggable_type' => Video::class,
            ]);
        }
    }

    public function testWithIncludePaths(): void
    {
        $video = Video::factory()->create();
        $tags = Tag::factory()->count(3)->create();

        $ids = $tags->map(fn(Tag $tag) => [
            'type' => 'tags',
            'id' => (string) $tag->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($video, 'tags')
            ->with('posts')
            ->sync($ids);

        $this->assertCount(3, $actual);
        $this->assertTrue($actual->every(fn(Tag $tag) => $tag->relationLoaded('posts')));
    }


    /**
     * The spec says:
     * "If a given type and id is already in the relationship, the server MUST NOT add it again."
     *
     * This test checks that duplicate ids are not added.
     */
    public function testWithDuplicates(): void
    {
        /** @var Video $video */
        $video = Video::factory()->create();
        $tags = Tag::factory()->count(3)->create();

        $video->tags()->attach($tags[2]);

        $ids = collect($tags)->push($tags[1])->map(fn(Tag $tag) => [
            'type' => 'tags',
            'id' => (string) $tag->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($video, 'tags')
            ->sync($ids);

        $this->assertCount(3, $actual);
        $this->assertSame(3, $video->tags()->count());
        $this->assertTags($tags, $actual);
    }
}
