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

namespace LaravelJsonApi\Eloquent\Tests\Acceptance\Relations\MorphTo;

use App\Models\Image;
use App\Models\Post;
use App\Models\User;

class QueryTest extends TestCase
{

    /**
     * @return array
     */
    public function modelProvider(): array
    {
        return [
            'post' => [Post::class],
            'user' => [User::class],
        ];
    }

    /**
     * @param string $modelClass
     * @dataProvider modelProvider
     */
    public function test(string $modelClass): void
    {
        $image = Image::factory()
            ->for($modelClass::factory(), 'imageable')
            ->create();

        $actual = $this->repository
            ->queryToOne($image, 'imageable')
            ->first();

        $this->assertTrue($image->imageable->is($actual));
    }

    public function testEmpty(): void
    {
        $image = Image::factory()->create();

        $actual = $this->repository
            ->queryToOne($image, 'imageable')
            ->first();

        $this->assertNull($actual);
    }

    /**
     * @return array
     */
    public function includePathProvider(): array
    {
        return [
            'post' => [Post::class, 'author,country', 'user'],
            'user' => [User::class, 'country,author', 'country'],
        ];
    }

    /**
     * @param string $modelClass
     * @param string $path
     * @param string $relation
     * @return void
     * @dataProvider includePathProvider
     */
    public function testWithIncludePaths(string $modelClass, string $path, string $relation): void
    {
        $image = Image::factory()
            ->for($modelClass::factory(), 'imageable')
            ->create();

        $actual = $this->repository
            ->queryToOne($image, 'imageable')
            ->with($path)
            ->first();

        $this->assertTrue($image->imageable->is($actual));
        $this->assertTrue($actual->relationLoaded($relation));
    }

    /**
     * @param string $modelClass
     * @param string $path
     * @dataProvider includePathProvider
     */
    public function testEmptyWithIncludePaths(string $modelClass, string $path): void
    {
        $image = Image::factory()->create();

        $actual = $this->repository
            ->queryToOne($image, 'imageable')
            ->with($path)
            ->first();

        $this->assertNull($actual);
    }

    public function testWithFilter(): void
    {
        $image = Image::factory()
            ->for(Post::factory(), 'imageable')
            ->create();

        $actual = $this->repository
            ->queryToOne($image, 'imageable')
            ->filter(['slug' => $image->imageable->slug])
            ->first();

        $this->assertTrue($image->imageable->is($actual));
    }

    public function testWithFilterReturnsNull(): void
    {
        $image = Image::factory()
            ->for(Post::factory(['slug' => 'foo-bar']), 'imageable')
            ->create();

        $actual = $this->repository
            ->queryToOne($image, 'imageable')
            ->filter(['slug' => 'baz-bat'])
            ->first();

        $this->assertNull($actual);
    }

    /**
     * In this scenario, the `email` filter is being used. This is valid
     * if the related imageable model is a user, but is not valid if it
     * is a post. In this scenario we expect `null` to be returned because
     * the `post` cannot match the supplied filter.
     */
    public function testWithFilterIsInvalidForModel(): void
    {
        $image = Image::factory()
            ->for(Post::factory(), 'imageable')
            ->create();

        $actual = $this->repository
            ->queryToOne($image, 'imageable')
            ->filter(['email' => 'john.doe@example.com'])
            ->first();

        $this->assertNull($actual);
    }

    public function testWithFilterAndIncludePaths(): void
    {
        $image = Image::factory()
            ->for(User::factory(), 'imageable')
            ->create();

        $actual = $this->repository
            ->queryToOne($image, 'imageable')
            ->filter(['email' => $image->imageable->email])
            ->with('phone,author')
            ->first();

        $this->assertTrue($image->imageable->is($actual));
        $this->assertTrue($actual->relationLoaded('phone'));
    }
}
