<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Eloquent\Tests\Acceptance\Filters;

use App\Models\Post;
use App\Schemas\PostSchema;
use Carbon\Carbon;

class WhereNotNullTest extends TestCase
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

    /**
     * @return void
     */
    public function testTrue(): void
    {
        $posts = Post::factory()->sequence(
            ['published_at' => null],
            ['published_at' => Carbon::now()],
        )->count(5)->create();

        $expected = $posts->whereNotNull('published_at');

        $actual = $this->schema
            ->repository()
            ->queryAll()
            ->filter(['published' => 'true'])
            ->get();

        $this->assertFilteredModels($expected, $actual);
    }

    /**
     * @return void
     */
    public function testFalse(): void
    {
        $posts = Post::factory()->sequence(
            ['published_at' => null],
            ['published_at' => Carbon::now()],
        )->count(5)->create();

        $expected = $posts->whereNull('published_at');

        $actual = $this->schema
            ->repository()
            ->queryAll()
            ->filter(['published' => 'false'])
            ->get();

        $this->assertFilteredModels($expected, $actual);
    }
}