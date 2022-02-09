<?php
/*
 * Copyright 2022 Cloud Creativity Limited
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

namespace LaravelJsonApi\Eloquent\Tests\Acceptance\Relations\HasManyThrough;

use App\Models\Country;
use App\Models\Post;
use App\Models\User;
use App\Schemas\PostSchema;
use LaravelJsonApi\Eloquent\Repository;
use LaravelJsonApi\Eloquent\Tests\Acceptance\TestCase;

class Test extends TestCase
{

    /**
     * @var Repository
     */
    private Repository $repository;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->schemas()->schemaFor('countries')->repository();
    }

    public function test(): void
    {
        $country = Country::factory()->create();
        $users = User::factory()->count(2)->create(['country_id' => $country]);

        $expected1 = Post::factory()->count(2)->create(['user_id' => $users[0]]);
        $expected2 = Post::factory()->count(3)->create(['user_id' => $users[1]]);

        // should be ignored.
        Post::factory()
            ->for(User::factory()->for(Country::factory()))
            ->create();

        $actual = $this->repository
            ->queryToMany($country, 'posts')
            ->cursor();

        $this->assertPosts($expected1->merge($expected2), $actual);

        // as the relationship is countable, we expect the count to be loaded so the relationship meta is complete.
        $this->assertEquals(count($expected1) + count($expected2), $country->posts_count);
    }

    public function testWithIncludePaths(): void
    {
        $country = Country::factory()->create();
        $users = User::factory()->count(2)->create(['country_id' => $country]);

        $expected1 = Post::factory()->count(2)->create(['user_id' => $users[0]]);
        $expected2 = Post::factory()->count(3)->create(['user_id' => $users[1]]);

        $actual = $this->repository
            ->queryToMany($country, 'posts')
            ->with('author')
            ->cursor();

        $this->assertPosts($expected1->merge($expected2), $actual);
        $this->assertTrue($actual->every(fn(Post $post) => $post->relationLoaded('user')));
    }

    public function testWithDefaultEagerLoading(): void
    {
        $this->createSchemaWithDefaultEagerLoading(PostSchema::class, 'user');

        $country = Country::factory()->create();
        $users = User::factory()->count(2)->create(['country_id' => $country]);

        $expected1 = Post::factory()->count(2)->create(['user_id' => $users[0]]);
        $expected2 = Post::factory()->count(3)->create(['user_id' => $users[1]]);

        $actual = $this->repository
            ->queryToMany($country, 'posts')
            ->cursor();

        $this->assertPosts($expected1->merge($expected2), $actual);
        $this->assertTrue($actual->every(fn(Post $post) => $post->relationLoaded('user')));
    }

    public function testWithDefaultEagerLoadingAndIncludePaths(): void
    {
        $this->createSchemaWithDefaultEagerLoading(PostSchema::class, 'user');

        $country = Country::factory()->create();
        $users = User::factory()->count(2)->create(['country_id' => $country]);

        $expected1 = Post::factory()->count(2)->create(['user_id' => $users[0]]);
        $expected2 = Post::factory()->count(3)->create(['user_id' => $users[1]]);

        $actual = $this->repository
            ->queryToMany($country, 'posts')
            ->with('tags')
            ->cursor();

        $this->assertPosts($expected1->merge($expected2), $actual);
        $this->assertTrue($actual->every(fn(Post $post) => $post->relationLoaded('user')));
        $this->assertTrue($actual->every(fn(Post $post) => $post->relationLoaded('tags')));
    }

    public function testWithFilter(): void
    {
        $country = Country::factory()->create();
        $users = User::factory()->count(2)->create(['country_id' => $country]);

        $posts1 = Post::factory()->count(2)->create(['user_id' => $users[0]]);
        $posts2 = Post::factory()->count(3)->create(['user_id' => $users[1]]);

        $expected = collect([$posts1[0], $posts2[1]])->sortBy('slug');
        $slugs = $expected->pluck('slug')->push('foo-bar')->all();

        $actual = $this->repository
            ->queryToMany($country, 'posts')
            ->filter(compact('slugs'))
            ->cursor();

        $this->assertPosts($expected, $actual->sortBy('slug'));
    }

    public function testWithSort(): void
    {
        $country = Country::factory()->create();
        $users = User::factory()->count(2)->create(['country_id' => $country]);

        $posts1 = Post::factory()->count(2)->create(['user_id' => $users[0]]);
        $posts2 = Post::factory()->count(3)->create(['user_id' => $users[1]]);

        $expected = $posts1->merge($posts2)->sortByDesc('slug');

        $actual = $this->repository
            ->queryToMany($country, 'posts')
            ->sort('-slug')
            ->cursor();

        $this->assertPosts($expected, $actual);
    }

    /**
     * @param iterable $expected
     * @param iterable $actual
     * @return void
     */
    protected function assertPosts(iterable $expected, iterable $actual): void
    {
        $expected = collect($expected)
            ->map($fn = static fn(Post $post) => $post->getKey())
            ->values()
            ->all();

        $actual = collect($actual)->map($fn)->values()->all();

        $this->assertSame($expected, $actual);
    }
}
