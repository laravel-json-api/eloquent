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
