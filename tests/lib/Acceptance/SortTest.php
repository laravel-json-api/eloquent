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

namespace LaravelJsonApi\Eloquent\Tests\Acceptance;

use App\Models\Comment;
use App\Models\Post;
use App\Schemas\PostSchema;
use PHPUnit\Framework\MockObject\MockObject;

class SortTest extends TestCase
{

    /**
     * @var PostSchema|MockObject
     */
    private PostSchema $posts;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->posts = $this
            ->getMockBuilder(PostSchema::class)
            ->onlyMethods(['defaultSort'])
            ->setConstructorArgs(['server' => $this->server()])
            ->getMock();
    }

    public function testId(): void
    {
        $posts = Post::factory()->count(3)->create();

        $actual = $this->posts
            ->repository()
            ->queryAll()
            ->sort('-id')
            ->get();

        $this->assertPosts($posts->sortByDesc('id'), $actual);
    }

    public function testIdWithFilter(): void
    {
        $posts = Post::factory()->count(3)->create();

        Post::factory()->count(2)->create();

        $ids = $posts
            ->map(fn(Post $post) => (string) $post->getRouteKey())
            ->all();

        $actual = $this->posts
            ->repository()
            ->queryAll()
            ->filter(['id' => $ids])
            ->sort('-id')
            ->get();

        $this->assertPosts($posts->sortByDesc('id'), $actual);
    }

    public function testAttribute(): void
    {
        $posts = Post::factory()->count(3)->create();

        $actual = $this->posts
            ->repository()
            ->queryAll()
            ->sort('title')
            ->get();

        $this->assertPosts($posts->sortBy('title'), $actual);
    }

    public function testSortable(): void
    {
        $posts = Post::factory()->count(3)->create();

        Comment::factory()->count(3)->create([
            'commentable_type' => Post::class,
            'commentable_id' => $posts[0]->getKey(),
        ]);

        Comment::factory()->count(2)->create([
            'commentable_type' => Post::class,
            'commentable_id' => $posts[2]->getKey(),
        ]);

        $actual = $this->posts
            ->repository()
            ->queryAll()
            ->sort('comments')
            ->get();

        $this->assertPosts([$posts[1], $posts[2], $posts[0]], $actual);
    }

    public function testDefaultSort(): void
    {
        $this->posts
            ->expects($this->once())
            ->method('defaultSort')
            ->willReturn('-id');

        $posts = Post::factory()->count(3)->create();

        $actual = $this->posts
            ->repository()
            ->queryAll()
            ->get();

        $this->assertPosts($posts->sortByDesc('id'), $actual);
    }

    /**
     * @param $expected
     * @param $actual
     * @return void
     */
    private function assertPosts($expected, $actual): void
    {
        $expected = collect($expected)
            ->map(fn(Post $post) => $post->getKey())
            ->values()
            ->all();

        $actual = collect($actual)
            ->map(fn(Post $post) => $post->getKey())
            ->values()
            ->all();

        $this->assertSame($expected, $actual);
    }
}
