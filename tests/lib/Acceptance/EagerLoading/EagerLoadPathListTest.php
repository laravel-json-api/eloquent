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

use LaravelJsonApi\Core\Query\RelationshipPath;
use LaravelJsonApi\Eloquent\QueryBuilder\EagerLoading\EagerLoadPathList;
use LaravelJsonApi\Eloquent\Tests\Acceptance\TestCase;

class EagerLoadPathListTest extends TestCase
{

    public function test(): void
    {
        $path = new EagerLoadPathList(
            $this->schemas()->schemaFor('posts'),
            RelationshipPath::fromString('author.country'),
        );

        $this->assertSame(['user.country'], iterator_to_array($path->paths()));
    }

    public function testMorphTo1(): void
    {
        $path = new EagerLoadPathList(
            $this->schemas()->schemaFor('posts'),
            RelationshipPath::fromString('image.imageable')
        );

        $this->assertSame(['image.imageable'], iterator_to_array($path->paths()));
    }

    public function testMorphTo2(): void
    {
        $path = new EagerLoadPathList(
            $this->schemas()->schemaFor('posts'),
            RelationshipPath::fromString('image.imageable.phone'),
        );

        $this->assertSame(['image.imageable'], iterator_to_array($path->paths()));
    }

    public function testMorphToMany1(): void
    {
        $path = new EagerLoadPathList(
            $this->schemas()->schemaFor('posts'),
            RelationshipPath::fromString('media'),
        );

        $this->assertSame(['images', 'videos'], iterator_to_array($path->paths()));
    }

    public function testMorphToMany2(): void
    {
        $path = new EagerLoadPathList(
            $this->schemas()->schemaFor('posts'),
            RelationshipPath::fromString('media.imageable'),
        );

        $this->assertSame(['images.imageable', 'videos'], iterator_to_array($path->paths()));
    }

    public function testMorphToMany3(): void
    {
        $path = new EagerLoadPathList(
            $this->schemas()->schemaFor('countries'),
            RelationshipPath::fromString('posts.media.imageable')
        );

        $this->assertSame([
            'posts.images.imageable',
            'posts.videos',
        ], iterator_to_array($path->paths()));
    }
}
