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

namespace LaravelJsonApi\Eloquent\Tests\Acceptance\Relations\MorphToMany\TagVideos;

use App\Models\Tag;
use App\Models\Video;

class QueryTest extends TestCase
{

    public function test(): void
    {
        $tag = Tag::factory()
            ->has(Video::factory()->count(3))
            ->create();

        // should be ignored.
        Tag::factory()
            ->has(Video::factory()->count(2))
            ->create();

        $actual = $this->repository
            ->queryToMany($tag, 'videos')
            ->cursor();

        $this->assertVideos($tag->videos()->get(), $actual);
    }

    public function testWithIncludePaths(): void
    {
        $tag = Tag::factory()
            ->has(Video::factory()->count(3))
            ->create();

        $actual = $this->repository
            ->queryToMany($tag, 'videos')
            ->with('comments')
            ->cursor();

        $this->assertVideos($tag->videos()->get(), $actual);
        $this->assertTrue($actual->every(fn(Video $video) => $video->relationLoaded('comments')));
    }

    public function testWithFilter(): void
    {
        $tag = Tag::factory()
            ->has(Video::factory()->count(3))
            ->create();

        $videos = $tag->videos()->get();

        $expected = $videos->take(2);
        $ids = $expected->map(fn (Video $video) => $video->getRouteKey())->all();

        $actual = $this->repository
            ->queryToMany($tag, 'videos')
            ->filter(['id' => $ids])
            ->cursor();

        $this->assertVideos($expected, $actual);
    }

    public function testWithPivotFilter(): void
    {
        /** @var Tag $tag */
        $tag = Tag::factory()
            ->hasAttached(Video::factory()->count(2), ['approved' => true])
            ->create();

        $expected = $tag->videos()->get();

        $notApproved = Video::factory()->count(2)->create();
        $tag->videos()->saveMany($notApproved, ['approved' => false]);

        $actual = $this->repository
            ->queryToMany($tag, 'videos')
            ->filter(['approved' => true])
            ->cursor();

        $this->assertVideos($expected, $actual);
    }

    public function testWithSort(): void
    {
        $tag = Tag::factory()
            ->has(Video::factory()->count(3))
            ->create();

        $videos = $tag->videos()->get();

        $expected = $videos->sortByDesc('id');

        $actual = $this->repository
            ->queryToMany($tag, 'videos')
            ->sort('-id')
            ->cursor();

        $this->assertVideos($expected, $actual);
    }

}
