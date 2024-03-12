<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Eloquent\Tests\Acceptance\Relations\MorphTo;

use App\Models\Image;
use App\Models\Post;
use App\Models\Role;
use App\Models\Tag;
use App\Models\User;
use App\Schemas\PostSchema;
use App\Schemas\UserSchema;

class QueryTest extends TestCase
{

    /**
     * @return array
     */
    public static function modelProvider(): array
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
    public static function includePathProvider(): array
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
     * @return array
     */
    public static function defaultEagerLoadProvider(): array
    {
        return [
            'post' => [PostSchema::class, Post::class, 'user'],
            'user' => [UserSchema::class, User::class, 'country'],
        ];
    }

    /**
     * @param string $schemaClass
     * @param string $modelClass
     * @param string $relation
     * @dataProvider defaultEagerLoadProvider
     */
    public function testWithDefaultEagerLoading(string $schemaClass, string $modelClass, string $relation): void
    {
        $this->createSchemaWithDefaultEagerLoading($schemaClass, $relation);

        $image = Image::factory()
            ->for($modelClass::factory(), 'imageable')
            ->create();

        $actual = $this->repository
            ->queryToOne($image, 'imageable')
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

    public function testPostWithCount(): void
    {
        $post = Post::factory()
            ->has(Tag::factory()->count(2))
            ->create();

        $image = Image::factory()
            ->for($post, 'imageable')
            ->create();

        $actual = $this->repository
            ->queryToOne($image, 'imageable')
            ->withCount('tags,roles')
            ->first();

        $this->assertTrue($image->imageable->is($actual));
        $this->assertEquals(2, $image->imageable->tags_count);
    }

    public function testUserWithCount(): void
    {
        $user = User::factory()
            ->has(Role::factory()->count(2))
            ->create();

        $image = Image::factory()
            ->for($user, 'imageable')
            ->create();

        $actual = $this->repository
            ->queryToOne($image, 'imageable')
            ->withCount('tags,roles')
            ->first();

        $this->assertTrue($image->imageable->is($actual));
        $this->assertEquals(2, $image->imageable->roles_count);
    }

    public function testWithCountAndInclude(): void
    {
        $post = Post::factory()
            ->has(Tag::factory()->count(2))
            ->create();

        $image = Image::factory()
            ->for($post, 'imageable')
            ->create();

        $actual = $this->repository
            ->queryToOne($image, 'imageable')
            ->with('tags,roles')
            ->withCount('tags,roles')
            ->first();

        $this->assertTrue($image->imageable->is($actual));
        $this->assertTrue($image->imageable->relationLoaded('tags'));
        $this->assertEquals(2, $image->imageable->tags_count);
    }
}
