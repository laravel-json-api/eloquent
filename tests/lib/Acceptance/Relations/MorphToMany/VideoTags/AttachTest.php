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

namespace LaravelJsonApi\Eloquent\Tests\Acceptance\Relations\MorphToMany\VideoTags;

use App\Models\Tag;
use App\Models\User;
use App\Models\Video;
use App\Schemas\TagSchema;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class AttachTest extends TestCase
{

    public function test(): void
    {
        $this->actingAs(User::factory()->create(['admin' => true]));

        $video = Video::factory()
            ->hasAttached(Tag::factory()->count(3), ['approved' => false])
            ->create();

        /** We force the relation to be loaded before the change, so that we can test it is unset. */
        $existing = clone $video->tags;
        $expected = Tag::factory()->count(2)->create();

        $ids = $expected->map(fn(Tag $tag) => [
            'type' => 'tags',
            'id' => (string) $tag->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($video, 'tags')
            ->attach($ids);

        $this->assertInstanceOf(EloquentCollection::class, $actual);
        $this->assertTags($expected, $actual);
        $this->assertSame(5, $video->tags()->count());

        // as the relationship is countable, we expect the count to be loaded so the relationship meta is complete.
        $this->assertEquals(5, $video->tags_count);

        /**
         * We expect the relation to be unloaded because we know it has changed in the
         * database, but we don't know what it now is in its entirety.
         */
        $this->assertFalse($video->relationLoaded('tags'));

        foreach ($existing as $tag) {
            $this->assertDatabaseHas('taggables', [
                'approved' => false, // we expect existing to keep their value
                'tag_id' => $tag->getKey(),
                'taggable_id' => $video->getKey(),
                'taggable_type' => Video::class,
            ]);
        }

        foreach ($expected as $tag) {
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
        $video = Video::factory()->create();
        $tags = Tag::factory()->count(2)->create();

        $ids = $tags->map(fn(Tag $tag) => [
            'type' => 'tags',
            'id' => (string) $tag->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($video, 'tags')
            ->with('posts')
            ->attach($ids);

        $this->assertTags($tags, $actual);
        $this->assertTrue($actual->every(fn(Tag $tag) => $tag->relationLoaded('posts')));
    }

    public function testWithDefaultEagerLoading(): void
    {
        $this->createSchemaWithDefaultEagerLoading(TagSchema::class, 'posts');

        $video = Video::factory()->create();
        $tags = Tag::factory()->count(2)->create();

        $ids = $tags->map(fn(Tag $tag) => [
            'type' => 'tags',
            'id' => (string) $tag->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($video, 'tags')
            ->attach($ids);

        $this->assertTags($tags, $actual);
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
        $tags = Tag::factory()->count(2)->create();

        $video->tags()->attach($tags[1]);

        $ids = collect($tags)->push($tags[0], $tags[1])->map(fn(Tag $tag) => [
            'type' => 'tags',
            'id' => (string) $tag->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($video, 'tags')
            ->attach($ids);

        $this->assertTags($tags, $actual);
        $this->assertSame(2, $video->tags()->count());

        foreach ($tags as $tag) {
            $this->assertDatabaseHas('taggables', [
                'approved' => false,
                'tag_id' => $tag->getKey(),
                'taggable_id' => $video->getKey(),
                'taggable_type' => Video::class,
            ]);
        }
    }
}
