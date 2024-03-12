<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Eloquent\Tests\Acceptance;

use App\Models\Comment;
use App\Models\Post;
use App\Schemas\PostSchema;

class CountableTest extends TestCase
{

    /**
     * @var PostSchema
     */
    private PostSchema $schema;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->schema = $this->schemas()->schemaFor('posts');
    }

    public function testQueryOneWithId(): void
    {
        $post = Post::factory()
            ->has(Comment::factory()->count(3))
            ->create();

        Comment::factory()->create();

        $actual = $this->schema
            ->repository()
            ->queryOne((string) $post->getRouteKey())
            ->withCount('comments')
            ->first();

        $this->assertTrue($post->is($actual));
        $this->assertEquals(3, $actual->comments_count);
    }

    public function testQueryOneWithModel(): void
    {
        $post = Post::factory()
            ->has(Comment::factory()->count(3))
            ->create();

        Comment::factory()->create();

        $actual = $this->schema
            ->repository()
            ->queryOne($post)
            ->withCount('comments')
            ->first();

        $this->assertSame($post, $actual);
        $this->assertEquals(3, $post->comments_count);
    }

    public function testQueryAll(): void
    {
        $post1 = Post::factory()->create();
        $post2 = Post::factory()->has(Comment::factory()->count(2))->create();
        $post3 = Post::factory()->has(Comment::factory()->count(3))->create();

        $actual = $this->schema
            ->repository()
            ->queryAll()
            ->withCount('comments')
            ->cursor();

        $this->assertCount(3, $actual);

        $actual = collect($actual)->keyBy('id');

        $this->assertEquals(0, $actual[$post1->getKey()]->comments_count);
        $this->assertEquals(2, $actual[$post2->getKey()]->comments_count);
        $this->assertEquals(3, $actual[$post3->getKey()]->comments_count);
    }

    public function testCountAs(): void
    {
        $this->schema
            ->toMany('comments')
            ->countAs('total_comments');

        $post = Post::factory()
            ->has(Comment::factory()->count(3))
            ->create();

        Comment::factory()->create();

        $actual = $this->schema
            ->repository()
            ->queryOne((string) $post->getRouteKey())
            ->withCount('comments')
            ->first();

        $this->assertTrue($post->is($actual));
        $this->assertEquals(3, $actual->total_comments);
    }
}
