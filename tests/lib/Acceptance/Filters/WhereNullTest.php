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

namespace LaravelJsonApi\Eloquent\Tests\Acceptance\Filters;

use App\Models\Post;
use App\Schemas\PostSchema;
use Carbon\Carbon;

class WhereNullTest extends TestCase
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

        $expected = $posts->whereNull('published_at');

        $actual = $this->schema
            ->repository()
            ->queryAll()
            ->filter(['draft' => 'true'])
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

        $expected = $posts->whereNotNull('published_at');

        $actual = $this->schema
            ->repository()
            ->queryAll()
            ->filter(['draft' => 'false'])
            ->get();

        $this->assertFilteredModels($expected, $actual);
    }
}