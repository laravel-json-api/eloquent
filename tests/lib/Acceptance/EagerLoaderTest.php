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

namespace LaravelJsonApi\Eloquent\Tests\Acceptance;

use App\Models\Post;
use App\Models\User;

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
                ['user.country', 'comments.user.country', 'image.imageable'],
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
}
