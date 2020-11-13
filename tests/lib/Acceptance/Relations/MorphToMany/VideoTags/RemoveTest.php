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

class RemoveTest extends TestCase
{

    public function test(): void
    {
        $video = Video::factory()
            ->has(Tag::factory()->count(3))
            ->create();

        /** We force the relation to be loaded before the change, so that we can test it is unset. */
        $existing = clone $video->tags;
        $remove = $existing->take(2);
        $keep = $existing->last();

        $ids = $remove->map(fn(Tag $tag) => [
            'type' => 'tags',
            'id' => (string) $tag->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($video, 'tags')
            ->remove($ids);

        $this->assertInstanceOf(EloquentCollection::class, $actual);
        $this->assertTags($remove, $actual);
        $this->assertSame(1, $video->tags()->count());

        /**
         * We expect the relation to be unloaded because we know it has changed in the
         * database, but we don't know what it now is in its entirety.
         */
        $this->assertFalse($video->relationLoaded('tags'));

        $this->assertDatabaseHas('taggables', [
            'tag_id' => $keep->getKey(),
            'taggable_id' => $video->getKey(),
            'taggable_type' => Video::class,
        ]);

        foreach ($remove as $tag) {
            $this->assertDatabaseMissing('taggables', [
                'tag_id' => $tag->getKey(),
                'taggable_id' => $video->getKey(),
                'taggable_type' => Video::class,
            ]);
        }
    }

    public function testWithIncludePaths(): void
    {
        $video = Video::factory()
            ->has(Tag::factory()->count(3))
            ->create();

        $roles = clone $video->tags;

        $ids = $roles->map(fn(Tag $tag) => [
            'type' => 'tags',
            'id' => (string) $tag->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($video, 'tags')
            ->with('posts')
            ->remove($ids);

        $this->assertTags($roles, $actual);
        $this->assertTrue($actual->every(fn(Tag $tag) => $tag->relationLoaded('posts')));
    }
}
