<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Eloquent\Tests\Acceptance\EagerLoading;

use App\Models\Post;
use App\Models\User;
use LaravelJsonApi\Core\Query\IncludePaths;
use LaravelJsonApi\Eloquent\QueryBuilder\EagerLoading\EagerLoader;
use LaravelJsonApi\Eloquent\Tests\Acceptance\TestCase;

class EagerLoaderTest extends TestCase
{

    /**
     * @return array[]
     */
    public static function includePathsProvider(): array
    {
        return [
//            'images' => [
//                'images',
//                'imageable',
//                ['imageable'],
//            ],
            'posts' => [
                'posts',
                'author.country,comments.user.country,image.imageable',
                // return values are sorted
                // user auto includes profile as it is used in attributes
                [
                    'comments.user.country',
                    'comments.user.profile',
                    'image.imageable',
                    'user.country',
                    'user.profile',
                ],
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
                [
                    'country.posts.image',
                    'profile', // auto included for users
                ],
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
        $loader = $this->eagerLoader($type, $includePaths);

        $this->assertSame($expected, $loader->getRelations());

        $this->assertEmpty($loader->getMorphs());
    }

    /**
     * @see https://laravel.com/docs/eloquent-relationships#nested-eager-loading-morphto-relationships
     */
    public function testMorphTo(): void
    {
        $loader = $this->eagerLoader('images', [
            'imageable.author.country',
            'imageable.country',
        ]);

        $this->assertSame([
            'imageable' => [
                // profile is auto included for users as it is used for attributes.
                Post::class => ['user.country', 'user.profile'],
                User::class => ['country', 'profile'],
            ],
        ], $loader->getMorphs());
    }

    /**
     * @param string $resourceType
     * @param $includePaths
     * @return EagerLoader
     */
    private function eagerLoader(string $resourceType, $includePaths): EagerLoader
    {
        return new EagerLoader(
            $this->schemas(),
            $this->schemas()->schemaFor($resourceType),
            IncludePaths::cast($includePaths),
        );
    }
}
