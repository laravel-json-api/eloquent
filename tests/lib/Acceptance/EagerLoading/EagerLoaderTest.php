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

namespace LaravelJsonApi\Eloquent\Tests\Acceptance\EagerLoading;

use App\Models\Post;
use App\Models\User;
use App\Schemas\PostSchema;
use LaravelJsonApi\Eloquent\Tests\Acceptance\TestCase;

class EagerLoaderTest extends TestCase
{

    /**
     * @return array[]
     */
    public function includePathsProvider(): array
    {
        return [
            'images' => [
                'images',
                'imageable',
                ['imageable'],
            ],
            'posts' => [
                'posts',
                'author.country,comments.user.country,image.imageable',
                // return values are sorted
                ['comments.user.country', 'image.imageable', 'user.country'],
            ],
            'posts morph-to-many' => [
                'posts',
                'media.imageable,media.comments',
                ['images.imageable', 'videos.comments'],
            ],
            'tags' => [
                'tags',
                'posts',
                ['posts'],
            ],
            'user' => [
                'users',
                'country.posts.image',
                ['country.posts.image'],
            ],
        ];
    }

    /**
     * @param string $type
     * @param $includePaths
     * @param array $expected
     * @dataProvider includePathsProvider
     */
    public function test(string $type, $includePaths, array $expected): void
    {
        $loader = $this
            ->schemas()
            ->schemaFor($type)
            ->loader();

        $this->assertSame($expected, $loader->toRelations(
            $includePaths
        ));

        $this->assertEmpty($loader->toMorphs($includePaths));
    }

    /**
     * @see https://laravel.com/docs/eloquent-relationships#nested-eager-loading-morphto-relationships
     */
    public function testMorphTo(): void
    {
        $loader = $this
            ->schemas()
            ->schemaFor('images')
            ->loader();

        $actual = $loader->toMorphs([
            'imageable.author.country',
            'imageable.country',
        ]);

        $this->assertSame([
            'imageable' => [
                Post::class => ['user.country'],
                User::class => ['country'],
            ],
        ], $actual);
    }

    /**
     * @return array
     */
    public function defaultEagerLoadProvider(): array
    {
        return [
            'posts:no include paths' => [
                'posts',
                null,
                ['user']
            ],
            'posts: with include paths' => [
                'posts',
                'author.country,comments.user.country,image.imageable',
                [
                    // sorted
                    'comments.user.country',
                    'image.imageable',
                    'user.country',
                ],
            ],
            'tags' => [
                'tags',
                'posts',
                ['posts.user'],
            ],
            'user' => [
                'users',
                'country.posts.image',
                ['country.posts.image', 'country.posts.user'],
            ],
        ];
    }

    /**
     * @param string $type
     * @param $includePaths
     * @param array $expected
     * @dataProvider defaultEagerLoadProvider
     */
    public function testWithDefaultEagerLoad(string $type, $includePaths, array $expected): void
    {
        $this->createSchemaWithDefaultEagerLoading(PostSchema::class, 'user');

        $loader = $this
            ->schemas()
            ->schemaFor($type)
            ->loader();

        $this->assertSame($expected, $loader->toRelations($includePaths));
    }

    /**
     * @return array
     */
    public function morphToDefaultEagerLoadProvider(): array
    {
        return [
            [
                'imageable',
                [
                    'imageable' => [
                        Post::class => ['user'],
                    ],
                ],
            ],
            [
                'imageable.country',
                [
                    'imageable' => [
                        Post::class => ['user'],
                        User::class => ['country'],
                    ],
                ],
            ],
            [
                'imageable.author.country,imageable.country',
                [
                    'imageable' => [
                        Post::class => ['user.country'],
                        User::class => ['country'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param $includePaths
     * @param array $expected
     * @dataProvider morphToDefaultEagerLoadProvider
     */
    public function testMorphToWithDefaultEagerLoad($includePaths, array $expected): void
    {
        $this->createSchemaWithDefaultEagerLoading(PostSchema::class, 'user');

        $loader = $this
            ->schemas()
            ->schemaFor('images')
            ->loader();

        $actual = $loader->toMorphs($includePaths);

        $this->assertSame($expected, $actual);
    }
}
