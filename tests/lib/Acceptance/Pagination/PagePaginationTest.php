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

namespace LaravelJsonApi\Eloquent\Tests\Acceptance\Pagination;

use App\Models\Post;
use App\Models\Video;
use App\Schemas\PostSchema;
use App\Schemas\VideoSchema;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\LazyCollection;
use LaravelJsonApi\Contracts\Pagination\Page;
use LaravelJsonApi\Core\Support\Arr;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Tests\Acceptance\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class PagePaginationTest extends TestCase
{

    /**
     * @var PagePagination
     */
    private PagePagination $paginator;

    /**
     * @var PostSchema|MockObject
     */
    private PostSchema $posts;

    /**
     * @var VideoSchema|MockObject
     */
    private VideoSchema $videos;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->paginator = PagePagination::make();

        $this->posts = $this
            ->getMockBuilder(PostSchema::class)
            ->onlyMethods(['pagination', 'defaultPagination'])
            ->setConstructorArgs(['server' => $this->server()])
            ->getMock();

        $this->videos = $this
            ->getMockBuilder(VideoSchema::class)
            ->onlyMethods(['pagination', 'defaultPagination'])
            ->setConstructorArgs(['server' => $this->server()])
            ->getMock();

        $this->posts->method('pagination')->willReturn($this->paginator);
        $this->videos->method('pagination')->willReturn($this->paginator);

        $this->app->instance(PostSchema::class, $this->posts);
        $this->app->instance(VideoSchema::class, $this->videos);

        AbstractPaginator::currentPathResolver(fn() => url('/api/v1/posts'));
    }

    /**
     * An schema's default pagination is used if no pagination parameters are sent.
     *
     * @see https://github.com/cloudcreativity/laravel-json-api/issues/131
     */
    public function testDefaultPagination(): void
    {
        $this->posts->method('defaultPagination')->willReturn(['number' => '1']);

        $meta = [
            'currentPage' => 1,
            'from' => 1,
            'lastPage' => 1,
            'perPage' => 15,
            'to' => 4,
            'total' => 4,
        ];

        $links = [
            'first' => [
                'href' => 'http://localhost/api/v1/posts?' . Arr::query([
                        'page' => ['number' => '1', 'size' => '15']
                    ]),
            ],
            'last' => [
                'href' => 'http://localhost/api/v1/posts?' . Arr::query([
                        'page' => ['number' => '1', 'size' => '15']
                    ]),
            ],
        ];

        $posts = Post::factory()->count(4)->create();

        $page = $this->posts
            ->repository()
            ->queryAll()
            ->firstOrPaginate(null);

        $this->assertInstanceOf(Page::class, $page);
        $this->assertSame(['page' => $meta], $page->meta());
        $this->assertSame($links, $page->links()->toArray());
        $this->assertPage($posts, $page);
    }

    public function testNoDefaultPagination(): void
    {
        $this->posts->method('defaultPagination')->willReturn(null);

        $posts = Post::factory()->count(4)->create();

        $actual = $this->posts
            ->repository()
            ->queryAll()
            ->firstOrPaginate(null);

        $this->assertInstanceOf(LazyCollection::class, $actual);
        $this->assertPage($posts, $actual);
    }

    /**
     * If the schema has default pagination, but the client has used
     * a singular filter AND not provided paging parameters, we
     * expect the singular filter to be respected I.e. the default
     * pagination must be ignored.
     */
    public function testDefaultPaginationWithSingularFilter(): void
    {
        $this->posts->method('defaultPagination')->willReturn(['number' => 1]);

        $posts = Post::factory()->count(4)->create();
        $post = $posts[2];

        $actual = $this->posts
            ->repository()
            ->queryAll()
            ->filter(['slug' => $post->slug])
            ->firstOrPaginate(null);

        $this->assertInstanceOf(Post::class, $actual);
        $this->assertTrue($post->is($actual));
    }

    /**
     * Same as previous test but the filter does not match any models.
     */
    public function testDefaultPaginationWithSingularFilterThatDoesNotMatch(): void
    {
        $this->posts->method('defaultPagination')->willReturn(['number' => 1]);

        $post = Post::factory()->make();

        $actual = $this->posts
            ->repository()
            ->queryAll()
            ->filter(['slug' => $post->slug])
            ->firstOrPaginate(null);

        $this->assertNull($actual);
    }

    /**
     * If the client uses a singular filter, but provides page parameters,
     * they should get a page - not a zero-to-one response.
     */
    public function testPaginationWithSingularFilter(): void
    {
        $posts = Post::factory()->count(4)->create();
        $post = $posts[2];

        $actual = $this->posts
            ->repository()
            ->queryAll()
            ->filter(['slug' => $post->slug])
            ->firstOrPaginate(['number' => '1']);

        $this->assertInstanceOf(Page::class, $actual);
        $this->assertPage([$post], $actual);
    }

    /**
     * If the search does not match any models, then there are no pages.
     */
    public function testNoPages(): void
    {
        $meta = [
            'currentPage' => 1,
            'from' => 0,
            'lastPage' => 1,
            'perPage' => 3,
            'to' => 0,
            'total' => 0,
        ];

        $links = [
            'first' => [
                'href' => $first = 'http://localhost/api/v1/posts?' . Arr::query([
                    'page' => ['number' => '1', 'size' => '3']
                ]),
            ],
            'last' => [
                'href' => $first,
            ],
        ];

        $page = $this->posts->repository()->queryAll()->paginate(['number' => '1', 'size' => '3']);

        $this->assertSame(['page' => $meta], $page->meta());
        $this->assertSame($links, $page->links()->toArray());
        $this->assertEmpty($page);
    }

    public function testPage1(): void
    {
        $posts = Post::factory()->count(4)->create();

        $this->paginator->withLengthAwarePagination();

        $meta = [
            'currentPage' => 1,
            'from' => 1,
            'lastPage' => 2,
            'perPage' => 3,
            'to' => 3,
            'total' => 4,
        ];

        $links = [
            'first' => [
                'href' => 'http://localhost/api/v1/posts?' . Arr::query([
                        'page' => ['number' => '1', 'size' => '3']
                    ]),
            ],
            'last' => [
                'href' => 'http://localhost/api/v1/posts?' . Arr::query([
                        'page' => ['number' => '2', 'size' => '3']
                    ]),
            ],
            'next' => [
                'href' => 'http://localhost/api/v1/posts?' . Arr::query([
                        'page' => ['number' => '2', 'size' => '3']
                    ]),
            ],
        ];

        $page = $this->posts->repository()->queryAll()->paginate(['number' => '1', 'size' => '3']);

        $this->assertSame(['page' => $meta], $page->meta());
        $this->assertSame($links, $page->links()->toArray());
        $this->assertPage($posts->take(3), $page);
    }

    public function testPage2(): void
    {
        $posts = Post::factory()->count(4)->create();

        $this->paginator->withCamelCaseMeta();

        $meta = [
            'currentPage' => 2,
            'from' => 4,
            'lastPage' => 2,
            'perPage' => 3,
            'to' => 4,
            'total' => 4,
        ];

        $links = [
            'first' => [
                'href' => 'http://localhost/api/v1/posts?' . Arr::query([
                        'page' => ['number' => '1', 'size' => '3']
                    ]),
            ],
            'last' => [
                'href' => 'http://localhost/api/v1/posts?' . Arr::query([
                        'page' => ['number' => '2', 'size' => '3']
                    ]),
            ],
            'prev' => [
                'href' => 'http://localhost/api/v1/posts?' . Arr::query([
                        'page' => ['number' => '1', 'size' => '3']
                    ]),
            ],
        ];

        $page = $this->posts->repository()->queryAll()->paginate(['number' => '2', 'size' => '3']);

        $this->assertSame(['page' => $meta], $page->meta());
        $this->assertSame($links, $page->links()->toArray());
        $this->assertPage([$posts->last()], $page);
    }

    /**
     * When no page size is provided, the default is used from the model.
     */
    public function testItUsesModelDefaultPerPage(): void
    {
        $expected = (new Post())->getPerPage();
        $posts = Post::factory()->count($expected + 1)->create();

        $meta = [
            'currentPage' => 1,
            'from' => 1,
            'lastPage' => 2,
            'perPage' => $expected,
            'to' => $expected,
            'total' => $expected + 1,
        ];

        $links = [
            'first' => [
                'href' => 'http://localhost/api/v1/posts?' . Arr::query([
                    'page' => ['number' => '1', 'size' => $expected]
                ]),
            ],
            'last' => [
                'href' => 'http://localhost/api/v1/posts?' . Arr::query([
                    'page' => ['number' => '2', 'size' => $expected]
                ]),
            ],
            'next' => [
                'href' => 'http://localhost/api/v1/posts?' . Arr::query([
                    'page' => ['number' => '2', 'size' => $expected]
                ]),
            ],
        ];

        $page = $this->posts->repository()->queryAll()->paginate(['number' => '1']);

        $this->assertSame(['page' => $meta], $page->meta());
        $this->assertSame($links, $page->links()->toArray());
        $this->assertPage($posts->take($expected), $page);
    }

    /**
     * The default per-page value can be overridden on the paginator.
     */
    public function testItUsesDefaultPerPage(): void
    {
        $expected = (new Post())->getPerPage() - 5;

        $this->paginator->withDefaultPerPage($expected);

        $posts = Post::factory()->count($expected + 1)->create();

        $meta = [
            'currentPage' => 1,
            'from' => 1,
            'lastPage' => 2,
            'perPage' => $expected,
            'to' => $expected,
            'total' => $expected + 1,
        ];

        $links = [
            'first' => [
                'href' => 'http://localhost/api/v1/posts?' . Arr::query([
                        'page' => ['number' => '1', 'size' => $expected]
                    ]),
            ],
            'last' => [
                'href' => 'http://localhost/api/v1/posts?' . Arr::query([
                        'page' => ['number' => '2', 'size' => $expected]
                    ]),
            ],
            'next' => [
                'href' => 'http://localhost/api/v1/posts?' . Arr::query([
                        'page' => ['number' => '2', 'size' => $expected]
                    ]),
            ],
        ];

        $page = $this->posts->repository()->queryAll()->paginate(['number' => '1']);

        $this->assertSame(['page' => $meta], $page->meta());
        $this->assertSame($links, $page->links()->toArray());
        $this->assertPage($posts->take($expected), $page);
    }

    public function testPageWithReverseKey(): void
    {
        $posts = Post::factory()->count(4)->create();

        $page = $this->posts->repository()->queryAll()
            ->sort('-id')
            ->paginate(['number' => '1', 'size' => '3']);

        $this->assertPage($posts->reverse()->take(3), $page);
    }

    /**
     * If we are sorting by a column that might not be unique, we expect
     * the page to always be returned in a particular order i.e. by the
     * key column.
     *
     * @see https://github.com/cloudcreativity/laravel-json-api/issues/313
     */
    public function testDeterministicOrder(): void
    {
        $first = Video::factory()->create([
            'created_at' => Carbon::now()->subWeek(),
        ]);

        $second = Video::factory()->create([
            'uuid' => 'f3b3bea3-dca0-4ef9-b06c-43583a7e6118',
            'created_at' => Carbon::now()->subHour(),
        ]);

        $third = Video::factory()->create([
            'uuid' => 'd215f35c-feb7-4cc5-9631-61742f00d0b2',
            'created_at' => $second->created_at,
        ]);

        $fourth = Video::factory()->create([
            'uuid' => 'cbe17134-d7e2-4509-ba2c-3b3b5e3b2cbe',
            'created_at' => $second->created_at,
        ]);

        $page = $this->videos->repository()->queryAll()
            ->sort('createdAt')
            ->paginate(['number' => '1', 'size' => '3']);

        $this->assertPage([$first, $fourth, $third], $page);
    }

    public function testCustomPageKeys(): void
    {
        Post::factory()->count(4)->create();

        $this->paginator->withPageKey('page')->withPerPageKey('limit');

        $links = [
            'first' => [
                'href' => 'http://localhost/api/v1/posts?' . Arr::query([
                        'page' => ['limit' => '3', 'page' => '1']
                    ]),
            ],
            'last' => [
                'href' => 'http://localhost/api/v1/posts?' . Arr::query([
                        'page' => ['limit' => '3', 'page' => '2']
                    ]),
            ],
            'next' => [
                'href' => 'http://localhost/api/v1/posts?' . Arr::query([
                        'page' => ['limit' => '3', 'page' => '2']
                    ]),
            ],
        ];

        $page = $this->posts->repository()->queryAll()->paginate(['page' => '1', 'limit' => '3']);

        $this->assertSame($links, $page->links()->toArray());
    }

    public function testSimplePagination(): void
    {
        Post::factory()->count(4)->create();

        $this->paginator->withSimplePagination();

        $meta = [
            'currentPage' => 1,
            'from' => 1,
            'perPage' => 3,
            'to' => 3,
        ];

        $links = [
            'first' => [
                'href' => 'http://localhost/api/v1/posts?' . Arr::query([
                        'page' => ['number' => '1', 'size' => '3']
                    ]),
            ],
            'next' => [
                'href' => 'http://localhost/api/v1/posts?' . Arr::query([
                        'page' => ['number' => '2', 'size' => '3']
                    ]),
            ],
        ];

        $page = $this->posts->repository()->queryAll()->paginate(['number' => '1', 'size' => '3']);

        $this->assertSame(['page' => $meta], $page->meta());
        $this->assertSame($links, $page->links()->toArray());
    }

    /**
     * Same as previous test, but as there are only 3 resources and the page size is 3,
     * we don't get a next link.
     */
    public function testSimplePaginationWithoutNext(): void
    {
        Post::factory()->count(3)->create();

        $this->paginator->withSimplePagination();

        $meta = [
            'currentPage' => 1,
            'from' => 1,
            'perPage' => 3,
            'to' => 3,
        ];

        $links = [
            'first' => [
                'href' => 'http://localhost/api/v1/posts?' . Arr::query([
                        'page' => ['number' => '1', 'size' => '3']
                    ]),
            ],
        ];

        $page = $this->posts->repository()->queryAll()->paginate(['number' => '1', 'size' => '3']);

        $this->assertSame(['page' => $meta], $page->meta());
        $this->assertSame($links, $page->links()->toArray());
    }

    public function testSnakeCaseMetaAndCustomMetaKey(): void
    {
        $posts = Post::factory()->count(4)->create();

        $this->paginator->withMetaKey('paginator')->withSnakeCaseMeta();

        $meta = [
            'current_page' => 1,
            'from' => 1,
            'last_page' => 2,
            'per_page' => 3,
            'to' => 3,
            'total' => 4,
        ];

        $page = $this->posts->repository()->queryAll()->paginate(['number' => '1', 'size' => '3']);

        $this->assertSame(['paginator' => $meta], $page->meta());
        $this->assertPage($posts->take(3), $page);
    }

    public function testDashCaseMeta(): void
    {
        $posts = Post::factory()->count(4)->create();

        $this->paginator->withDashCaseMeta();

        $meta = [
            'current-page' => 1,
            'from' => 1,
            'last-page' => 2,
            'per-page' => 3,
            'to' => 3,
            'total' => 4,
        ];

        $page = $this->posts->repository()->queryAll()->paginate(['number' => '1', 'size' => '3']);

        $this->assertSame(['page' => $meta], $page->meta());
        $this->assertPage($posts->take(3), $page);
    }

    public function testMetaNotNested(): void
    {
        $posts = Post::factory()->count(4)->create();

        $this->paginator->withoutNestedMeta();

        $meta = [
            'currentPage' => 1,
            'from' => 1,
            'lastPage' => 2,
            'perPage' => 3,
            'to' => 3,
            'total' => 4,
        ];

        $page = $this->posts->repository()->queryAll()->paginate(['number' => '1', 'size' => '3']);

        $this->assertSame($meta, $page->meta());
        $this->assertPage($posts->take(3), $page);
    }

    public function testItCanRemoveMeta(): void
    {
        $posts = Post::factory()->count(4)->create();

        $this->paginator->withoutMeta();

        $links = [
            'first' => [
                'href' => 'http://localhost/api/v1/posts?' . Arr::query([
                    'page' => ['number' => '1', 'size' => '3']
                ]),
            ],
            'last' => [
                'href' => 'http://localhost/api/v1/posts?' . Arr::query([
                    'page' => ['number' => '2', 'size' => '3']
                ]),
            ],
            'next' => [
                'href' => 'http://localhost/api/v1/posts?' . Arr::query([
                    'page' => ['number' => '2', 'size' => '3']
                ]),
            ],
        ];

        $page = $this->posts->repository()->queryAll()->paginate(['size' => 3]);

        $this->assertEmpty($page->meta());
        $this->assertSame($links, $page->links()->toArray());
        $this->assertPage($posts->take(3), $page);
    }

    /**
     * Assert that the pages match.
     *
     * @param $expected
     * @param $actual
     */
    private function assertPage($expected, $actual): void
    {
        $expected = (new Collection($expected))->modelKeys();
        $actual = (new Collection($actual))->modelKeys();

        $this->assertSame(array_values($expected), array_values($actual));
    }

}
