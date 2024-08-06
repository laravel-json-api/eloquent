<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
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
use LaravelJsonApi\Core\Query\QueryParameters;
use LaravelJsonApi\Core\Support\Arr;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Pagination\CursorPagination;
use LaravelJsonApi\Eloquent\Tests\Acceptance\TestCase;
use LaravelJsonApi\Eloquent\Tests\EncodedId;
use PHPUnit\Framework\MockObject\MockObject;

class CursorPaginationTest extends TestCase
{

    /**
     * @var CursorPagination
     */
    private CursorPagination $paginator;

    /**
     * @var EncodedId|null
     */
    private ?EncodedId $encodedId = null;

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

        $this->paginator = CursorPagination::make(ID::make()->uuid());

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

        $this->posts->method('pagination')->willReturnCallback(fn () => $this->paginator);
        $this->videos->method('pagination')->willReturnCallback(fn () => $this->paginator);

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
        $this->posts->method('defaultPagination')->willReturn(['limit' => 10]);

        $meta = [
            'from' => $this->encodeCursor([
                "id" => "4",
                "_pointsToNextItems" => false
            ]),
            'hasMore' => false,
            'perPage' => 10,
            'to' =>  $this->encodeCursor([
                "id" => "1",
                "_pointsToNextItems" => true
            ]),
        ];

        $links = [

            'first' => [
                'href' => 'http://localhost/api/v1/posts?' . Arr::query([
                        'page' => ['limit' => '10']
                    ]),
            ]
        ];

        $posts = Post::factory()->count(4)->create();

        $page = $this->posts
            ->repository()
            ->queryAll()
            ->firstOrPaginate(null);
        $this->assertInstanceOf(Page::class, $page);
        $this->assertSame(['page' => $meta], $page->meta());
        $this->assertSame($links, $page->links()->toArray());
        $this->assertPage($posts->reverse(), $page);
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
        $this->posts->method('defaultPagination')->willReturn(['limit' => 1]);

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
        $this->posts->method('defaultPagination')->willReturn(['limit' => 1]);

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
            'from' => null,
            'hasMore' => false,
            'perPage' => 3,
            'to' => null,
        ];

        $links = [
            'first' => [
                'href' => $first = 'http://localhost/api/v1/posts?' . Arr::query([
                    'page' => ['limit' => '3']
                ]),
            ],
        ];

        $page = $this->posts->repository()->queryAll()->paginate(['limit' => '3']);

        $this->assertSame(['page' => $meta], $page->meta());
        $this->assertSame($links, $page->links()->toArray());
        $this->assertEmpty($page);
    }

    public function testWithoutCursor(): void
    {
        $posts = Post::factory()->count(4)->create();

        $meta = [
            'from' => $this->encodeCursor([
                "id" => "4",
                "_pointsToNextItems" => false
            ]),
            'hasMore' => true,
            'perPage' => 3,
            'to' => $this->encodeCursor([
                "id" => "2",
                "_pointsToNextItems" => true
            ])
        ];

        $links = [
            'first' => [
                'href' => 'http://localhost/api/v1/posts?' . Arr::query([
                        'page' => ['limit' => '3']
                    ]),
            ],
            'next' => [
                'href' => 'http://localhost/api/v1/posts?' . Arr::query([
                        'page' => ['after' => $this->encodeCursor([
                            "id" => "2",
                            "_pointsToNextItems" => true
                        ]), 'limit' => '3']
                    ]),
            ]
        ];

        $page = $this->posts->repository()->queryAll()->paginate(['limit' => '3']);

        $this->assertSame(['page' => $meta], $page->meta());
        $this->assertSame($links, $page->links()->toArray());
        $this->assertPage($posts->reverse()->take(3), $page);
    }

    public function testAfter(): void
    {
        $posts = Post::factory()->count(4)->create();

        $this->paginator->withCamelCaseMeta();

        $meta = [
            'from' => $this->encodeCursor([
                "id" => "1",
                "_pointsToNextItems" => false
            ]),
            'hasMore' => false,
            'perPage' => 3,
            'to' => $this->encodeCursor([
                "id" => "1",
                "_pointsToNextItems" => true
            ])
        ];

        $links = [
            'first' => [
                'href' => 'http://localhost/api/v1/posts?' . Arr::query([
                        'page' => ['limit' => '3']
                    ]),
            ],
            'prev' => [
                'href' => 'http://localhost/api/v1/posts?' . Arr::query([
                        'page' => ['before' => $this->encodeCursor([
                            "id" => "1",
                            "_pointsToNextItems" => false
                        ]), 'limit' => '3']
                    ]),
            ],
        ];

        $page = $this->posts->repository()->queryAll()->paginate(['after' => $this->encodeCursor([
            "id" => "2",
            "_pointsToNextItems" => true
        ]), 'limit' => '3']);

        $this->assertSame(['page' => $meta], $page->meta());
        $this->assertSame($links, $page->links()->toArray());
        $this->assertPage([$posts->first()], $page);
    }

    public function testAfterWithIdEncoding(): void
    {
        $this->withIdEncoding();

        $posts = Post::factory()->count(10)->create()->values();

        $expected = [$posts[6], $posts[5], $posts[4]];

        $meta = [
            'from' => $this->encodeCursor([
                "id" => 'TEST-7',
                "_pointsToNextItems" => false
            ]),
            'hasMore' => true,
            'perPage' => 3,
            'to' => $this->encodeCursor([
                "id" => 'TEST-5',
                "_pointsToNextItems" => true
            ]),
        ];

        $links = [
            'first' => [
                'href' => 'http://localhost/api/v1/posts?' . Arr::query([
                        'page' => ['limit' => '3']
                    ]),
            ],
            'next' =>  [
                'href' => 'http://localhost/api/v1/posts?' . Arr::query([
                        'page' => ['after' => $this->encodeCursor([
                            "id" => "TEST-5",
                            "_pointsToNextItems" => true
                        ]),'limit' => '3']
                    ]),],
            'prev' =>  [
                'href' => 'http://localhost/api/v1/posts?' . Arr::query([
                        'page' => ['before' => $this->encodeCursor([
                            "id" => "TEST-7",
                            "_pointsToNextItems" => false
                        ]),'limit' => '3']
                    ]),],
        ];

        $page = $this->posts->repository()->queryAll()->paginate([
            'after' => $this->encodeCursor([
                "id" => 'TEST-8',
                "_pointsToNextItems" => true
            ]),
            'limit' => 3,
        ]);
        $this->assertSame(['page' => $meta], $page->meta());
        $this->assertSame($links, $page->links()->toArray());
        $this->assertPage($expected, $page);
    }

    public function testBefore(): void
    {
        $posts = Post::factory()->count(4)->create();

        $this->paginator->withCamelCaseMeta();

        $meta = [
            'from' => $this->encodeCursor([
                "id" => "4",
                "_pointsToNextItems" => false
            ]),
            'hasMore' => true,
            'perPage' => 3,
            'to' => $this->encodeCursor([
                "id" => "2",
                "_pointsToNextItems" => true
            ]),
        ];

        $links = [
            'first' => [
                'href' => 'http://localhost/api/v1/posts?' . Arr::query([
                        'page' => ['limit' => '3']
                    ]),
            ],
            'prev' => [
                'href' => 'http://localhost/api/v1/posts?' . Arr::query([
                        'page' => ['before' => $this->encodeCursor([
                            "id" => "4",
                            "_pointsToNextItems" => false
                        ]), 'limit' => '3']
                    ]),
            ],
        ];

        $page = $this->posts->repository()->queryAll()->paginate(['before' => $this->encodeCursor([
            "id" => "1",
            "_pointsToNextItems" => false
        ]), 'limit' => '3']);

        $this->assertSame(['page' => $meta], $page->meta());
        $this->assertSame($links, $page->links()->toArray());
        $this->assertPage($posts->reverse()->take(3), $page);
    }

    public function testBeforeWithIdEncoding(): void
    {
        $this->withIdEncoding();

        $posts = Post::factory()->count(10)->create()->values();

        $expected = [$posts[6], $posts[5], $posts[4]];

        $meta = [
            'from' => $this->encodeCursor([
                "id" => 'TEST-7',
                "_pointsToNextItems" => false
            ]),
            'hasMore' => true,
            'perPage' => 3,
            'to' => $this->encodeCursor([
                "id" => 'TEST-5',
                "_pointsToNextItems" => true
            ]),
        ];

        $links = [
            'first' => [
                'href' => 'http://localhost/api/v1/posts?' . Arr::query([
                        'page' => ['limit' => '3']
                    ]),
            ],
            'prev' =>  [
                'href' => 'http://localhost/api/v1/posts?' . Arr::query([
                        'page' => ['before' => $this->encodeCursor([
                            "id" => "TEST-7",
                            "_pointsToNextItems" => false
                        ]),'limit' => '3']
                    ]),],
        ];

        $page = $this->posts->repository()->queryAll()->paginate([
            'before' => $this->encodeCursor([
              "id" => 'TEST-4',
              "_pointsToNextItems" => false
            ]),
            'limit' => 3,
        ]);
        $this->assertSame(['page' => $meta], $page->meta());
        $this->assertSame($links, $page->links()->toArray());
        $this->assertPage($expected, $page);
    }

    /**
     * When no page size is provided, the default is used from the model.
     */
    public function testItUsesModelDefaultPerPage(): void
    {
        $expected = (new Post())->getPerPage();
        $posts = Post::factory()->count($expected + 1)->create();

        $meta = [
            'from' => $this->encodeCursor([
                "id" => (string) ($expected + 1),
                "_pointsToNextItems" => false
            ]),
            'hasMore' => true,
            'perPage' => $expected,
            'to' => $this->encodeCursor([
                "id" => '2',
                "_pointsToNextItems" => true
            ]),

        ];

        $links = [
            'first' => [
                'href' => 'http://localhost/api/v1/posts?' . Arr::query([
                    'page' => ['limit' => $expected]
                ]),
            ],
            'next' => [
                'href' => 'http://localhost/api/v1/posts?' . Arr::query([
                    'page' => ['after' => $this->encodeCursor([
                        "id" => '2',
                        "_pointsToNextItems" => true
                    ]), 'limit' => $expected]
                ]),
            ],
        ];

        $page = $this->posts->repository()->queryAll()->paginate(['number' => '1']);

        $this->assertSame(['page' => $meta], $page->meta());
        $this->assertSame($links, $page->links()->toArray());
        $this->assertPage($posts->reverse()->take($expected), $page);
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
            'from' => $this->encodeCursor([
                "id" => (string) ($expected + 1),
                "_pointsToNextItems" => false
            ]),
            'hasMore' => true,
            'perPage' => $expected,
            'to' => $this->encodeCursor([
                "id" => '2',
                "_pointsToNextItems" => true
            ]),
        ];

        $links = [
            'first' => [
                'href' => 'http://localhost/api/v1/posts?' . Arr::query([
                        'page' => ['limit' => $expected]
                    ]),
            ],
            'next' => [
                'href' => 'http://localhost/api/v1/posts?' . Arr::query([
                        'page' => ['after' => $this->encodeCursor([
                            "id" => '2',
                            "_pointsToNextItems" => true
                        ]), 'limit' => $expected]
                    ]),
            ],
        ];

        $page = $this->posts->repository()->queryAll()->paginate(['number' => '1']);

        $this->assertSame(['page' => $meta], $page->meta());
        $this->assertSame($links, $page->links()->toArray());
        $this->assertPage($posts->reverse()->take($expected), $page);
    }

    public function testPageWithReverseKey(): void
    {
        $posts = Post::factory()->count(4)->create();

        $page = $this->posts->repository()->queryAll()
            ->sort('id')
            ->paginate(['limit' => '3']);

        $this->assertPage($posts->take(3), $page);
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
            'created_at' => Carbon::now()->subHour(),
        ]);

        $third = Video::factory()->create([
            'created_at' => $second->created_at,
        ]);

        $fourth = Video::factory()->create([
            'created_at' => $second->created_at,
        ]);

        $page = $this->videos->repository()->queryAll()
            ->sort('createdAt')
            ->paginate(['limit' => '3']);

        $this->assertPage([$first, $fourth, $third], $page);

        $page = $this->videos->repository()->queryAll()
            ->sort('-createdAt')
            ->paginate(['limit' => '3']);

        $this->assertPage([$fourth, $third, $second], $page);
    }

    public function testMultipleSorts(): void
    {
        $first = Video::factory()->create([
            'title' => 'b',
            'created_at' => Carbon::now()->subWeek(),
        ]);

        $second = Video::factory()->create([
            'title' => 'a',
            'created_at' => Carbon::now()->subHour(),
        ]);

        $third = Video::factory()->create([
            'title' => 'b',
            'created_at' => $second->created_at,
        ]);

        $fourth = Video::factory()->create([
            'title' => 'a',
            'created_at' => $second->created_at,
        ]);

        $page = $this->videos->repository()->queryAll()
            ->sort('title,createdAt')
            ->paginate(['limit' => '3']);

        $this->assertPage([$fourth, $second, $first], $page);

        $page = $this->videos->repository()->queryAll()
            ->sort('title,-createdAt')
            ->paginate(['limit' => '3']);

        $this->assertPage([$fourth, $second, $third], $page);
    }

    public function testWithoutKeySort(): void
    {
        $this->paginator->withoutKeySort();

        $first = Video::factory()->create([
            'title' => 'a',
        ]);

        $second = Video::factory()->create([
            'title' => 'a',
        ]);

        $third = Video::factory()->create([
            'title' => 'c',
        ]);

        $fourth = Video::factory()->create([
            'title' => 'b',
        ]);

        $page = $this->videos->repository()->queryAll()
            ->sort('title')
            ->paginate(['limit' => '3']);

        $this->assertPage([$first, $second, $fourth], $page);

        $this->paginator->withKeySort();

        $page = $this->videos->repository()->queryAll()
            ->sort('title')
            ->paginate(['limit' => '3']);

        $this->assertPage([$second, $first, $fourth], $page);

    }


    public function testCustomPageKeys(): void
    {
        Post::factory()->count(4)->create();

        $this->paginator->withAfterKey('next')->withBeforeKey('prev')->withLimitKey('perPage');

        $links = [
            'first' => [
                'href' => 'http://localhost/api/v1/posts?' . Arr::query([
                        'page' => ['perPage' => '3']
                    ]),
            ],
            'next' => [
                'href' => 'http://localhost/api/v1/posts?' . Arr::query([
                        'page' => ['next' => $this->encodeCursor(
                            ["id" => "2", "_pointsToNextItems" => true]
                        ), 'perPage' => '3']
                    ]),
            ],
        ];

        $page = $this->posts->repository()->queryAll()->paginate(['perPage' => '3']);

        $this->assertSame($links, $page->links()->toArray());


        $links = [
            'first' => [
                'href' => 'http://localhost/api/v1/posts?' . Arr::query([
                        'page' => ['perPage' => '3']
                    ]),
            ],
            'prev' => [
                'href' => 'http://localhost/api/v1/posts?' . Arr::query([
                        'page' => ['perPage' => '3', 'prev' => $this->encodeCursor(
                            ["id" => "1", "_pointsToNextItems" => false]
                        )]
                    ]),
            ],
        ];

        $page = $this->posts->repository()->queryAll()->paginate(['next' => $this->encodeCursor(
            ["id" => "2", "_pointsToNextItems" => true]
        ), 'perPage' => '3']);

        $this->assertSame($links, $page->links()->toArray());
    }

    public function testSnakeCaseMetaAndCustomMetaKey(): void
    {
        $posts = Post::factory()->count(4)->create();

        $this->paginator->withMetaKey('paginator')->withSnakeCaseMeta();

        $meta = [
            'from' => $this->encodeCursor([
                "id" => "4",
                "_pointsToNextItems" => false
            ]),
            'has_more' => true,
            'per_page' => 3,
            'to' => $this->encodeCursor([
                "id" => "2",
                "_pointsToNextItems" => true
            ])
        ];

        $page = $this->posts->repository()->queryAll()->paginate(['limit' => '3']);

        $this->assertSame(['paginator' => $meta], $page->meta());
        $this->assertPage($posts->reverse()->take(3), $page);
    }

    public function testDashCaseMeta(): void
    {
        $posts = Post::factory()->count(4)->create();

        $this->paginator->withDashCaseMeta();

        $meta = [
            'from' => $this->encodeCursor([
                "id" => "4",
                "_pointsToNextItems" => false
            ]),
            'has-more' => true,
            'per-page' => 3,
            'to' => $this->encodeCursor([
                "id" => "2",
                "_pointsToNextItems" => true
            ])
        ];

        $page = $this->posts->repository()->queryAll()->paginate(['limit' => '3']);

        $this->assertSame(['page' => $meta], $page->meta());
        $this->assertPage($posts->reverse()->take(3), $page);
    }

    public function testMetaNotNested(): void
    {
        $posts = Post::factory()->count(4)->create();

        $this->paginator->withoutNestedMeta();

        $meta = [
            'from' => $this->encodeCursor([
                "id" => "4",
                "_pointsToNextItems" => false
            ]),
            'hasMore' => true,
            'perPage' => 3,
            'to' => $this->encodeCursor([
                "id" => "2",
                "_pointsToNextItems" => true
            ])
        ];

        $page = $this->posts->repository()->queryAll()->paginate(['limit' => '3']);

        $this->assertSame($meta, $page->meta());
        $this->assertPage($posts->reverse()->take(3), $page);
    }

    public function testItCanRemoveMeta(): void
    {
        $posts = Post::factory()->count(4)->create();

        $this->paginator->withoutMeta();

        $links = [
            'first' => [
                'href' => 'http://localhost/api/v1/posts?' . Arr::query([
                    'page' => ['limit' => '3']
                ]),
            ],
            'next' => [
                'href' => 'http://localhost/api/v1/posts?' . Arr::query([
                    'page' => ['after' => $this->encodeCursor([
                        "id" => "2",
                        "_pointsToNextItems" => true
                    ]), 'limit' => '3']
                ]),
            ],
        ];

        $page = $this->posts->repository()->queryAll()->paginate(['limit' => 3]);

        $this->assertEmpty($page->meta());
        $this->assertSame($links, $page->links()->toArray());
        $this->assertPage($posts->reverse()->take(3), $page);
    }

    public function testUrlsIncludeOtherQueryParameters(): void
    {
        $posts = Post::factory()->count(6)->create();
        $slugs = $posts->take(4)->pluck('slug')->implode(',');

        $links = [
            'first' => [
                'href' => 'http://localhost/api/v1/posts?' . Arr::query([
                        'fields' => $fields = [
                            'posts' => 'author,slug,title',
                            'users' => 'name',
                        ],
                        'filter' => ['slugs' => $slugs],
                        'include' => 'author',
                        'page' => ['limit' => '3'],
                    ]),
            ],
            'next' => [
                'href' => 'http://localhost/api/v1/posts?' . Arr::query([
                        'fields' => $fields,
                        'filter' => ['slugs' => $slugs],
                        'include' => 'author',
                        'page' => ['after' => $this->encodeCursor([
                            "id" => "2",
                            "_pointsToNextItems" => true
                        ]), 'limit' => '3'],
                    ]),
            ],
        ];

        $query = QueryParameters::make()
            ->setFilters(['slugs' => $slugs])
            ->setSparseFieldSets($fields)
            ->setIncludePaths('author');

        $page = $this->posts
            ->repository()
            ->queryAll()
            ->withQuery($query)
            ->paginate(['limit' => 3]);

        $this->assertSame($links, $page->links()->toArray());
    }

    public function testWithTotal()
    {
        $this->paginator->withTotal();

        $meta = [
            'from' => $this->encodeCursor([
                "id" => "4",
                "_pointsToNextItems" => false
            ]),
            'hasMore' => true,
            'perPage' => 3,
            'to' => $this->encodeCursor([
                "id" => "2",
                "_pointsToNextItems" => true
            ]),
            'total' => 4,
        ];

        Post::factory()->count(4)->create();

        $page = $this->posts
            ->repository()
            ->queryAll()
            ->paginate(['limit' => 3]);

        $this->assertInstanceOf(Page::class, $page);
        $this->assertSame(['page' => $meta], $page->meta());

        $page = $this->posts
            ->repository()
            ->queryAll()
            ->paginate(['after' => $this->encodeCursor([
                "id" => "2",
                "_pointsToNextItems" => true
            ]), 'limit' => 3]);

        $this->assertInstanceOf(Page::class, $page);
        $this->assertArrayHasKey('page', $page->meta());
        $this->assertArrayHasKey('total', $page->meta()['page']);
        $this->assertEquals(4, $page->meta()['page']['total']);

    }

    public function testWithTotalOnFirstPage()
    {
        $this->paginator->withTotalOnFirstPage();

        $meta = [
            'from' => $this->encodeCursor([
                "id" => "4",
                "_pointsToNextItems" => false
            ]),
            'hasMore' => true,
            'perPage' => 3,
            'to' => $this->encodeCursor([
                "id" => "2",
                "_pointsToNextItems" => true
            ]),
            'total' => 4,
        ];

        Post::factory()->count(4)->create();

        $page = $this->posts
            ->repository()
            ->queryAll()
            ->paginate(['limit' => 3]);

        $this->assertInstanceOf(Page::class, $page);
        $this->assertSame(['page' => $meta], $page->meta());

        $page = $this->posts
            ->repository()
            ->queryAll()
            ->paginate(['after' => $this->encodeCursor([
                "id" => "2",
                "_pointsToNextItems" => true
            ]), 'limit' => 3]);

        $this->assertInstanceOf(Page::class, $page);
        $this->assertArrayHasKey('page', $page->meta());
        $this->assertArrayNotHasKey('total', $page->meta()['page']);
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

    /**
     * @return void
     */
    private function withIdEncoding(): void
    {
        $this->paginator = CursorPagination::make(
            $this->encodedId = new EncodedId()
        );
    }

    private function encodeCursor(array $params) : string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($params)));
    }

}
