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

namespace LaravelJsonApi\Eloquent\Tests\Acceptance\Relations\MorphOne;

use App\Models\Image;
use App\Models\Post;

class QueryTest extends TestCase
{

    public function test(): void
    {
        $image = Image::factory()
            ->for(Post::factory(), 'imageable')
            ->create();

        $actual = $this->repository
            ->queryToOne($image->imageable, 'image')
            ->first();

        $this->assertTrue($image->is($actual));
    }

    public function testWithIncludePaths(): void
    {
        $image = Image::factory()
            ->for(Post::factory(), 'imageable')
            ->create();

        $actual = $this->repository
            ->queryToOne($image->imageable, 'image')
            ->with('imageable')
            ->first();

        $this->assertTrue($image->is($actual));
        $this->assertTrue($actual->relationLoaded('imageable'));
    }

    public function testWithFilter(): void
    {
        $image = Image::factory()
            ->for(Post::factory(), 'imageable')
            ->create();

        $actual = $this->repository
            ->queryToOne($image->imageable, 'image')
            ->filter(['id' => [(string) $image->getRouteKey()]])
            ->first();

        $this->assertTrue($image->is($actual));
    }

    public function testWithFilterReturnsNull(): void
    {
        $image = Image::factory()
            ->for(Post::factory(), 'imageable')
            ->create();

        $actual = $this->repository
            ->queryToOne($image->imageable, 'image')
            ->filter(['id' => ['999']])
            ->first();

        $this->assertNull($actual);
    }

    public function testEmpty(): void
    {
        $post = Post::factory()->create();

        $actual = $this->repository
            ->queryToOne($post, 'image')
            ->first();

        $this->assertNull($actual);
    }

    /**
     * If the relation is already loaded and there are no filters, the already
     * loaded model should be returned rather than executing a fresh query.
     */
    public function testAlreadyLoaded(): void
    {
        $image = Image::factory()
            ->for(Post::factory(), 'imageable')
            ->create();

        $expected = $image->imageable->image;

        $actual = $this->repository
            ->queryToOne($image->imageable, 'image')
            ->first();

        $this->assertSame($expected, $actual);
        $this->assertEmpty($actual->getRelations());
    }

    public function testAlreadyLoadedWithIncludePaths(): void
    {
        $image = Image::factory()
            ->for(Post::factory(), 'imageable')
            ->create();

        $expected = $image->imageable->image;

        $this->assertFalse($expected->relationLoaded('imageable'));

        $actual = $this->repository
            ->queryToOne($image->imageable, 'image')
            ->with('imageable')
            ->first();

        $this->assertSame($expected, $actual);
        $this->assertTrue($actual->relationLoaded('imageable'));
        $this->assertTrue($image->imageable->is($actual->imageable));
    }

    /**
     * If a filter is used when the relation is already loaded, we do need to
     * execute a database query to determine if the model matches the filters.
     */
    public function testAlreadyLoadedWithFilter(): void
    {
        $image = Image::factory()
            ->for(Post::factory(), 'imageable')
            ->create();

        $expected = $image->imageable->image;

        $actual = $this->repository
            ->queryToOne($image->imageable, 'image')
            ->filter(['id' => [(string) $image->getRouteKey()]])
            ->first();

        $this->assertNotSame($expected, $actual);
        $this->assertTrue($expected->is($actual));
    }

}
