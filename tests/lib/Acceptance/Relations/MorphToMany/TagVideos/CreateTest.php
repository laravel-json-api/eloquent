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

use App\Models\User;
use App\Models\Video;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class CreateTest extends TestCase
{

    public function test(): void
    {
        $videos = Video::factory()->count(2)->create();

        $this->actingAs(User::factory()->create(['admin' => true]));

        $tag = $this->repository->create()->store([
            'name' => 'My Tag',
            'videos' => $videos->map(fn(Video $video) => [
                'type' => 'videos',
                'id' => (string) $video->getRouteKey(),
            ])->all(),
        ]);

        $this->assertTrue($tag->relationLoaded('videos'));
        $this->assertInstanceOf(EloquentCollection::class, $actual = $tag->getRelation('videos'));
        $this->assertVideos($videos, $actual);

        foreach ($videos as $video) {
            $this->assertDatabaseHas('taggables', [
                'approved' => true,
                'tag_id' => $tag->getKey(),
                'taggable_id' => $video->getKey(),
                'taggable_type' => Video::class,
            ]);
        }
    }

    public function testEmpty(): void
    {
        $tag = $this->repository->create()->store([
            'name' => 'My Tag',
            'videos' => [],
        ]);

        $this->assertTrue($tag->relationLoaded('videos'));
        $this->assertEquals(new EloquentCollection(), $tag->getRelation('videos'));
    }

    /**
     * The spec says:
     * "If a given type and id is already in the relationship, the server MUST NOT add it again."
     *
     * This test checks that duplicate ids are not added.
     */
    public function testWithDuplicates(): void
    {
        $videos = Video::factory()->count(2)->create();

        $tag = $this->repository->create()->store([
            'name' => 'My Tag',
            'videos' => collect($videos)->push($videos[1])->map(fn(Video $video) => [
                'type' => 'videos',
                'id' => (string) $video->getRouteKey(),
            ])->all(),
        ]);

        $this->assertTrue($tag->relationLoaded('videos'));
        $this->assertInstanceOf(EloquentCollection::class, $actual = $tag->getRelation('videos'));
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
