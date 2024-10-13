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

class WhereAnyTest extends TestCase
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
    public function testWhereAny(): void
    {
        Post::factory()->count(5)->create();

        $title = Post::factory()->create(['title' => "foobar boofar"]);
        $content = Post::factory()->create(['content' => "boofar foobar"]);
        $slug = Post::factory()->create(['slug' => "totally_foobar"]);

        $expected = [$title,$content, $slug];

        $actual = $this->schema
            ->repository()
            ->queryAll()
            ->filter(['any' => '%foobar%'])
            ->get();

        $this->assertFilteredModels($expected, $actual);
    }
}
