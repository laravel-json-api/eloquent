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

use App\Models\Post;
use App\Models\Tag;
use App\Schemas\PostSchema;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use PHPUnit\Framework\MockObject\MockObject;

class FilterTest extends TestCase
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
            ->onlyMethods(['isSingular'])
            ->setConstructorArgs(['server' => $this->server()])
            ->getMock();
    }

    public function testFilter(): void
    {
        $posts = Post::factory()->count(5)->create();
        $expected = $posts->take(3);

        $ids = $expected
            ->map(fn(Post $post) => (string) $post->getRouteKey())
            ->all();

        $actual = $this->posts
            ->repository()
            ->queryAll()
            ->filter(['id' => $ids])
            ->firstOrMany();

        $this->assertInstanceOf(LazyCollection::class, $actual);
        $this->assertCount($expected->count(), $actual);

        $this->assertSame(
            $expected->sortBy('id')->pluck('id')->all(),
            $actual->sortBy('id')->pluck('id')->all(),
        );
    }

    public function testSingular(): void
    {
        $expected = Post::factory()->count(3)->create()[1];

        $actual = $this->posts
            ->repository()
            ->queryAll()
            ->filter(['slug' => $expected->slug])
            ->firstOrMany();

        $this->assertInstanceOf(Post::class, $actual);
        $this->assertTrue($expected->is($actual));
    }

    public function testSingularDoesNotMatch(): void
    {
        Post::factory()->count(2)->create();

        $expected = Post::factory()->make();

        $actual = $this->posts
            ->repository()
            ->queryAll()
            ->filter(['slug' => $expected->slug])
            ->firstOrMany();

        $this->assertNull($actual);
    }

    public function testSchemaSingular(): void
    {
        $expected = Post::factory()->count(3)->create()[1];

        $filter = ['id' => [(string) $expected->getRouteKey()]];

        $this->posts
            ->expects($this->once())
            ->method('isSingular')
            ->with($filter)
            ->willReturn(true);

        $actual = $this->posts
            ->repository()
            ->queryAll()
            ->filter($filter)
            ->firstOrMany();

        $this->assertInstanceOf(Post::class, $actual);
        $this->assertTrue($expected->is($actual));
    }

    public function testSchemaNotSingular(): void
    {
        $expected = Post::factory()->count(3)->create()[1];

        $filter = ['id' => [(string) $expected->getRouteKey()]];

        $this->posts
            ->expects($this->once())
            ->method('isSingular')
            ->with($filter)
            ->willReturn(false);

        $actual = $this->posts
            ->repository()
            ->queryAll()
            ->filter($filter)
            ->firstOrMany();

        $this->assertInstanceOf(LazyCollection::class, $actual);
        $this->assertCount(1, $actual);
        $this->assertTrue($expected->is($actual->first()));
    }

    public function testWhereHas(): void
    {
        $tag1 = Tag::factory()->create(['name' => 'Foo']);
        $tag2 = Tag::factory()->create(['name' => 'Bar']);

        $posts = Post::factory()->count(4)->create();

        $posts[0]->tags()->attach($tag1);
        $posts[1]->tags()->attach($tag2);
        $posts[2]->tags()->attach($tag1);
        $posts[2]->tags()->attach($tag2);

        $filter = [
            'tags' => [
                'name' => 'Bar',
            ],
        ];

        $actual = $this->posts
            ->repository()
            ->queryAll()
            ->filter($filter)
            ->get();

        $this->assertPosts([$posts[1], $posts[2]], $actual);
    }

    public function testWhereHasViaRelationFilter(): void
    {
        $tag1 = Tag::factory()->create(['name' => 'Foo']);
        $tag2 = Tag::factory()->create(['name' => 'Bar']);

        $posts = Post::factory()->count(4)->create();

        $posts[0]->tags()->attach($tag1, ['approved' => true]);
        $posts[1]->tags()->attach($tag2, ['approved' => false]);
        $posts[2]->tags()->attach($tag1, ['approved' => true]);
        $posts[2]->tags()->attach($tag2, ['approved' => false]);

        $filter = [
            'tags' => [
                'approved' => 'true',
            ],
        ];

        $actual = $this->posts
            ->repository()
            ->queryAll()
            ->filter($filter)
            ->get();

        $this->assertPosts([$posts[0], $posts[2]], $actual);
    }

    public function testWhereDoesntHave(): void
    {
        $tag1 = Tag::factory()->create(['name' => 'Foo']);
        $tag2 = Tag::factory()->create(['name' => 'Bar']);

        $posts = Post::factory()->count(4)->create();

        $posts[0]->tags()->attach($tag1);
        $posts[1]->tags()->attach($tag2);
        $posts[2]->tags()->attach($tag1);
        $posts[2]->tags()->attach($tag2);

        $filter = [
            'notTags' => [
                'name' => 'Bar',
            ],
        ];

        $actual = $this->posts
            ->repository()
            ->queryAll()
            ->filter($filter)
            ->get();

        $this->assertPosts([$posts[0], $posts[3]], $actual);
    }

    public function testWhereDoesntHaveViaRelationFilter(): void
    {
        $tag1 = Tag::factory()->create(['name' => 'Foo']);
        $tag2 = Tag::factory()->create(['name' => 'Bar']);

        $posts = Post::factory()->count(4)->create();

        $posts[0]->tags()->attach($tag1, ['approved' => true]);
        $posts[1]->tags()->attach($tag2, ['approved' => false]);
        $posts[2]->tags()->attach($tag1, ['approved' => true]);
        $posts[2]->tags()->attach($tag2, ['approved' => false]);

        $filter = [
            'notTags' => [
                'approved' => 'true',
            ],
        ];

        $actual = $this->posts
            ->repository()
            ->queryAll()
            ->filter($filter)
            ->get();

        $this->assertPosts([$posts[1], $posts[3]], $actual);
    }

    /**
     * @param iterable $expected
     * @param iterable $actual
     */
    private function assertPosts(iterable $expected, iterable $actual): void
    {
        $expected = Collection::make($expected)
            ->map(static fn(Post $post) => $post->getKey())
            ->sort()
            ->values()
            ->all();

        $actual = Collection::make($actual)
            ->map(static fn(Post $post) => $post->getKey())
            ->sort()
            ->values()
            ->all();

        $this->assertSame($expected, $actual);
    }
}
