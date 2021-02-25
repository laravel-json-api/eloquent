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

use App\Models\Video;
use App\Schemas\VideoSchema;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\LazyCollection;
use LaravelJsonApi\Core\Support\Arr;
use LaravelJsonApi\Eloquent\Pagination\CursorPage;
use LaravelJsonApi\Eloquent\Pagination\CursorPagination;
use LaravelJsonApi\Eloquent\Tests\Acceptance\TestCase;

class CursorPaginationTest extends TestCase
{

    /**
     * @var CursorPagination
     */
    private CursorPagination $paginator;

    /**
     * @var VideoSchema
     */
    private VideoSchema $videos;

    /**
     * @var Faker
     */
    private Faker $faker;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->paginator = CursorPagination::make();

        $this->videos = $this
            ->getMockBuilder(VideoSchema::class)
            ->onlyMethods(['pagination', 'defaultPagination'])
            ->setConstructorArgs(['server' => $this->server()])
            ->getMock();

        $this->videos->method('pagination')->willReturn($this->paginator);

        $this->app->instance(VideoSchema::class, $this->videos);

        AbstractPaginator::currentPathResolver(fn() => url('/api/v1/videos'));

        $this->faker = $this->app->make(Faker::class);
    }

    /**
     * A schema's default pagination is used if no pagination parameters are sent.
     *
     * @see https://github.com/cloudcreativity/laravel-json-api/issues/131
     */
    public function testDefaultPagination(): void
    {
        $this->videos->method('defaultPagination')->willReturn(['limit' => '3']);

        $videos = Video::factory()->count(5)->create([
            'created_at' => fn() => $this->faker->dateTime,
        ])->sortByDesc('created_at')->values();

        $expected = $videos->take(3);

        $meta = [
            'from' => $expected[0]->getRouteKey(),
            'hasMore' => true,
            'perPage' => 3,
            'to' => $expected[2]->getRouteKey(),
        ];

        $links = $this->createLinks($expected[0], $expected[2], 3);

        $page = $this->videos
            ->repository()
            ->queryAll()
            ->firstOrPaginate(null);

        $this->assertInstanceOf(CursorPage::class, $page);
        $this->assertSame(['page' => $meta], $page->meta());
        $this->assertSame($links, $page->links()->toArray());
        $this->assertPage($expected, $page);
    }

    public function testNoDefaultPagination(): void
    {
        $this->videos->method('defaultPagination')->willReturn(null);

        $videos = Video::factory()->count(4)->create();

        $actual = $this->videos
            ->repository()
            ->queryAll()
            ->firstOrPaginate(null);

        $this->assertInstanceOf(LazyCollection::class, $actual);
        $this->assertPage($videos, $actual);
    }

    /**
     * If the schema has default pagination, but the client has used
     * a singular filter AND not provided paging parameters, we
     * expect the singular filter to be respected I.e. the default
     * pagination must be ignored.
     */
    public function testDefaultPaginationWithSingularFilter(): void
    {
        $this->videos->method('defaultPagination')->willReturn(['limit' => 3]);

        $videos = Video::factory()->count(4)->create();
        $video = $videos[3];

        $actual = $this->videos
            ->repository()
            ->queryAll()
            ->filter(['slug' => $video->slug])
            ->firstOrPaginate(null);

        $this->assertInstanceOf(Video::class, $actual);
        $this->assertTrue($video->is($actual));
    }

    /**
     * If the client uses a singular filter, but provides page parameters,
     * they should get a page - not a zero-to-one response.
     */
    public function testPaginationWithSingularFilter(): void
    {
        $videos = Video::factory()->count(4)->create();
        $video = $videos[2];

        $actual = $this->videos
            ->repository()
            ->queryAll()
            ->filter(['slug' => $video->slug])
            ->firstOrPaginate(['limit' => '10']);

        $this->assertInstanceOf(CursorPage::class, $actual);
        $this->assertPage([$video], $actual);
    }

    public function testNoPages(): void
    {
        $meta = [
            'from' => null,
            'hasMore' => false,
            'perPage' => 10,
            'to' => null,
        ];

        $links = [
            'first' => [
                'href' => 'http://localhost/api/v1/videos?' . Arr::query([
                    'page' => ['limit' => 10],
                ]),
            ],
        ];

        $page = $this->videos->newQuery()->paginate(['limit' => 10]);

        $this->assertSame(['page' => $meta], $page->meta());
        $this->assertSame($links, $page->links()->toArray());
        $this->assertEmpty($page);
    }

    public function testOnlyLimit(): void
    {
        $videos = Video::factory()->count(5)->create([
            'created_at' => fn() => $this->faker->unique()->dateTime,
        ])->sortByDesc('created_at')->values();

        $meta = [
            'from' => $videos[0]->getRouteKey(),
            'hasMore' => true,
            'perPage' => 4,
            'to' => $videos[3]->getRouteKey(),
        ];

        $links = $this->createLinks($videos[0], $videos[3], 4);

        $page = $this->videos->newQuery()->paginate(['limit' => 4]);

        $this->assertSame(['page' => $meta], $page->meta());
        $this->assertSame($links, $page->links()->toArray());
        $this->assertPage($videos->take(4), $page);
    }

    public function testBefore(): void
    {
        $videos = Video::factory()->count(10)->create([
            'created_at' => fn() => $this->faker->unique()->dateTime,
        ])->sortByDesc('created_at')->values();

        $expected = [$videos[4], $videos[5], $videos[6]];

        $meta = [
            'from' => $expected[0]->getRouteKey(),
            'hasMore' => true,
            'perPage' => 3,
            'to' => $expected[2]->getRouteKey(),
        ];

        $links = $this->createLinks($expected[0], $expected[2], 3);

        $page = $this->videos->newQuery()->paginate([
            'before' => $videos[7]->getRouteKey(),
            'limit' => 3,
        ]);

        $this->assertSame(['page' => $meta], $page->meta());
        $this->assertSame($links, $page->links()->toArray());
        $this->assertPage($expected, $page);
    }

    public function testBeforeAscending(): void
    {
        $this->paginator->withAscending();

        $videos = Video::factory()->count(10)->create([
            'created_at' => fn() => $this->faker->unique()->dateTime,
        ])->sortBy('created_at')->values();

        $expected = [$videos[4], $videos[5], $videos[6]];

        $meta = [
            'from' => $expected[0]->getRouteKey(),
            'hasMore' => true,
            'perPage' => 3,
            'to' => $expected[2]->getRouteKey(),
        ];

        $links = $this->createLinks($expected[0], $expected[2], 3);

        $page = $this->videos->newQuery()->paginate([
            'before' => $videos[7]->getRouteKey(),
            'limit' => '3',
        ]);

        $this->assertSame(['page' => $meta], $page->meta());
        $this->assertSame($links, $page->links()->toArray());
        $this->assertPage($expected, $page);
    }

    public function testBeforeWithEqualDates(): void
    {
        $equal = Video::factory()->count(3)->create([
            'created_at' => now()->subMinute(),
        ])->sortByDesc('uuid')->values();

        Video::factory()->create(['created_at' => now()->subMinutes(2)]);

        $recent = Video::factory()->create(['created_at' => now()->subSecond()]);

        $expected = [$recent, $equal[0], $equal[1]];

        $meta = [
            'from' => $expected[0]->getRouteKey(),
            'hasMore' => true,
            'perPage' => 15,
            'to' => $expected[2]->getRouteKey(),
        ];

        $links = $this->createLinks($expected[0], $expected[2], 15);

        $page = $this->videos->newQuery()->paginate([
            'before' => $equal->last()->getRouteKey(),
        ]);

        $this->assertSame(['page' => $meta], $page->meta());
        $this->assertSame($links, $page->links()->toArray());
        $this->assertPage($expected, $page);
    }

    /**
     * If the before key does not exist, we expect the cursor builder
     * to throw an exception which would constitute an internal server error.
     * Applications should validate the id before passing it to the cursor.
     */
    public function testBeforeDoesNotExist(): void
    {
        $this->expectException(\OutOfRangeException::class);
        $this->expectExceptionMessage('does not exist');

        $this->videos->newQuery()->paginate([
            'before' => $this->faker->uuid,
        ]);
    }

    public function testAfter(): void
    {
        $videos = Video::factory()->count(10)->create([
            'created_at' => fn() => $this->faker->unique()->dateTime,
        ])->sortByDesc('created_at')->values();

        $expected = [$videos[4], $videos[5], $videos[6]];

        $meta = [
            'from' => $expected[0]->getRouteKey(),
            'hasMore' => true,
            'perPage' => 3,
            'to' => $expected[2]->getRouteKey(),
        ];

        $links = $this->createLinks($expected[0], $expected[2], 3);

        $page = $this->videos->newQuery()->paginate([
            'after' => $videos[3]->getRouteKey(),
            'limit' => '3',
        ]);

        $this->assertSame(['page' => $meta], $page->meta());
        $this->assertSame($links, $page->links()->toArray());
        $this->assertPage($expected, $page);
    }

    public function testAfterAscending(): void
    {
        $this->paginator->withAscending();

        $videos = Video::factory()->count(10)->create([
            'created_at' => fn() => $this->faker->unique()->dateTime,
        ])->sortBy('created_at')->values();

        $expected = [$videos[4], $videos[5], $videos[6]];

        $meta = [
            'from' => $expected[0]->getRouteKey(),
            'hasMore' => true,
            'perPage' => 3,
            'to' => $expected[2]->getRouteKey(),
        ];

        $links = $this->createLinks($expected[0], $expected[2], 3);

        $page = $this->videos->newQuery()->paginate([
            'after' => $videos[3]->getRouteKey(),
            'limit' => 3,
        ]);

        $this->assertSame(['page' => $meta], $page->meta());
        $this->assertSame($links, $page->links()->toArray());
        $this->assertPage($expected, $page);
    }

    public function testAfterWithoutMore(): void
    {
        $videos = Video::factory()->count(4)->create([
            'created_at' => fn() => $this->faker->unique()->dateTime,
        ])->sortByDesc('created_at')->values();

        $expected = [$videos[2], $videos[3]];

        $meta = [
            'from' => $expected[0]->getRouteKey(),
            'hasMore' => false,
            'perPage' => 10,
            'to' => $expected[1]->getRouteKey(),
        ];

        $links = $this->createLinks($expected[0], $expected[1], 10);
        unset($links['next']);

        $page = $this->videos->newQuery()->paginate([
            'after' => $videos[1]->getRouteKey(),
            'limit' => 10,
        ]);

        $this->assertSame(['page' => $meta], $page->meta());
        $this->assertSame($links, $page->links()->toArray());
        $this->assertPage($expected, $page);
    }

    public function testAfterWithEqualDates(): void
    {
        $equal = Video::factory()->count(3)->create([
            'created_at' => now()->subMinute(),
        ])->sortByDesc('uuid')->values();

        $oldest = Video::factory()->create(['created_at' => now()->subMinutes(2)]);

        Video::factory()->create(['created_at' => now()->subSecond()]);

        $expected = [$equal[1], $equal[2], $oldest];

        $meta = [
            'from' => $expected[0]->getRouteKey(),
            'hasMore' => false,
            'perPage' => 15,
            'to' => $expected[2]->getRouteKey(),
        ];

        $links = $this->createLinks($expected[0], $expected[2], 15);
        unset($links['next']);

        $page = $this->videos->newQuery()->paginate([
            'after' => $equal[0]->getRouteKey(),
        ]);

        $this->assertSame(['page' => $meta], $page->meta());
        $this->assertSame($links, $page->links()->toArray());
        $this->assertPage($expected, $page);
    }

    public function testAfterWithCustomKeys(): void
    {
        $this->paginator
            ->withBeforeKey('ending-before')
            ->withAfterKey('starting-after')
            ->withLimitKey('per-page');

        $videos = Video::factory()->count(6)->create([
            'created_at' => fn() => $this->faker->dateTime,
        ])->sortByDesc('created_at')->values();

        $expected = [$videos[2], $videos[3], $videos[4]];

        $meta = [
            'from' => $expected[0]->getRouteKey(),
            'hasMore' => true,
            'perPage' => 3,
            'to' => $expected[2]->getRouteKey(),
        ];

        $links = [
            'first' => [
                'href' => 'http://localhost/api/v1/videos?' . Arr::query([
                        'page' => [
                            'per-page' => 3,
                        ],
                    ]),
            ],
            'next' => [
                'href' => 'http://localhost/api/v1/videos?' . Arr::query([
                        'page' => [
                            'per-page' => 3,
                            'starting-after' => $expected[2]->getRouteKey(),
                        ],
                    ]),
            ],
            'prev' => [
                'href' => 'http://localhost/api/v1/videos?' . Arr::query([
                        'page' => [
                            'ending-before' => $expected[0]->getRouteKey(),
                            'per-page' => 3,
                        ],
                    ]),
            ],
        ];

        $page = $this->videos->newQuery()->paginate([
            'per-page' => '3',
            'starting-after' => $videos[1]->getRouteKey(),
        ]);

        $this->assertSame(['page' => $meta], $page->meta());
        $this->assertSame($links, $page->links()->toArray());
        $this->assertPage($expected, $page);
    }

    /**
     * If the after key does not exist, we expect the cursor builder
     * to throw an exception which would constitute an internal server error.
     * Applications should validate the id before passing it to the cursor.
     */
    public function testAfterDoesNotExist(): void
    {
        $this->expectException(\OutOfRangeException::class);
        $this->expectExceptionMessage('does not exist');

        $this->videos->newQuery()->paginate([
            'after' => $this->faker->uuid,
        ]);
    }

    /**
     * If we supply both the before and after ids, only the before should be used.
     */
    public function testBeforeAndAfter(): void
    {
        $videos = Video::factory()->count(6)->create([
            'created_at' => fn() => $this->faker->unique()->dateTime,
        ])->sortByDesc('created_at')->values();

        $expected = [$videos[2], $videos[3], $videos[4]];

        $meta = [
            'from' => $expected[0]->getRouteKey(),
            'hasMore' => true,
            'perPage' => 3,
            'to' => $expected[2]->getRouteKey(),
        ];

        $links = $this->createLinks($expected[0], $expected[2], 3);

        $page = $this->videos->newQuery()->paginate([
            'limit' => 3,
            'before' => $videos[5]->getRouteKey(),
            'after' => $videos[1]->getRouteKey(),
        ]);

        $this->assertSame(['page' => $meta], $page->meta());
        $this->assertSame($links, $page->links()->toArray());
        $this->assertPage($expected, $page);
    }

    /**
     * Test use of the cursor paginator where the pagination column is
     * identical to the identifier column.
     */
    public function testSameColumnAndIdentifier(): void
    {
        $this->paginator->withCursorColumn('uuid');

        $videos = Video::factory()->count(6)->create([
            'created_at' => fn() => $this->faker->unique()->dateTime,
        ])->sortByDesc('uuid')->values();

        $expected = [$videos[1], $videos[2], $videos[3]];

        $meta = [
            'from' => $expected[0]->getRouteKey(),
            'hasMore' => true,
            'perPage' => 3,
            'to' => $expected[2]->getRouteKey(),
        ];

        $links = $this->createLinks($expected[0], $expected[2], 3);

        $page = $this->videos->newQuery()->paginate([
            'limit' => '3',
            'before' => $videos[4]->getRouteKey(),
            'after' => $videos[1]->getRouteKey(),
        ]);

        $this->assertSame(['page' => $meta], $page->meta());
        $this->assertSame($links, $page->links()->toArray());
        $this->assertPage($expected, $page);
    }

    public function testSnakeCaseMetaAndCustomMetaKey(): void
    {
        $this->paginator->withMetaKey('cursor')->withSnakeCaseMeta();

        $videos = Video::factory()->count(6)->create([
            'created_at' => fn() => $this->faker->unique()->dateTime,
        ])->sortByDesc('created_at')->values();

        $expected = [$videos[1], $videos[2], $videos[3]];

        $meta = [
            'from' => $expected[0]->getRouteKey(),
            'has_more' => true,
            'per_page' => 3,
            'to' => $expected[2]->getRouteKey(),
        ];

        $page = $this->videos->newQuery()->paginate([
            'limit' => '3',
            'before' => $videos[4]->getRouteKey(),
        ]);

        $this->assertSame(['cursor' => $meta], $page->meta());
        $this->assertPage($expected, $page);
    }

    public function testDashCaseMeta(): void
    {
        $this->paginator->withDashCaseMeta();

        $videos = Video::factory()->count(6)->create([
            'created_at' => fn() => $this->faker->unique()->dateTime,
        ])->sortByDesc('created_at')->values();

        $expected = [$videos[1], $videos[2], $videos[3]];

        $meta = [
            'from' => $expected[0]->getRouteKey(),
            'has-more' => true,
            'per-page' => 3,
            'to' => $expected[2]->getRouteKey(),
        ];

        $page = $this->videos->newQuery()->paginate([
            'limit' => '3',
            'before' => $videos[4]->getRouteKey(),
        ]);

        $this->assertSame(['page' => $meta], $page->meta());
        $this->assertPage($expected, $page);
    }

    /**
     * Test that we can change the column on which we paginate.
     */
    public function testColumn(): void
    {
        $this->paginator->withCursorColumn('updated_at');

        $videos = Video::factory()->count(6)->create([
            'updated_at' => fn() => $this->faker->unique()->dateTime,
        ])->sortByDesc('updated_at')->values();

        $expected = [$videos[1], $videos[2], $videos[3]];

        $meta = [
            'from' => $expected[0]->getRouteKey(),
            'hasMore' => true,
            'perPage' => 3,
            'to' => $expected[2]->getRouteKey(),
        ];

        $links = $this->createLinks($expected[0], $expected[2], 3);

        $page = $this->videos->newQuery()->paginate([
            'limit' => '3',
            'before' => $videos[4]->getRouteKey(),
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
        $expected = (new Video())->getPerPage();

        $videos = Video::factory()->count($expected + 5)->create([
            'created_at' => fn() => $this->faker->dateTime,
        ])->sortByDesc('created_at')->values();

        $meta = [
            'from' => $videos[1]->getRouteKey(),
            'hasMore' => true,
            'perPage' => $expected,
            'to' => $videos[$expected]->getRouteKey(),
        ];

        $links = $this->createLinks($videos[1], $videos[$expected], $expected);

        $page = $this->videos->newQuery()->paginate(['after' => $videos[0]->getRouteKey()]);

        $this->assertSame(['page' => $meta], $page->meta());
        $this->assertSame($links, $page->links()->toArray());
        $this->assertPage($videos->skip(1)->take($expected), $page);
    }

    /**
     * The default per-page value can be overridden on the paginator.
     */
    public function testItUsesDefaultPerPage(): void
    {
        $expected = (new Video())->getPerPage() - 5;

        $this->paginator->withDefaultPerPage($expected);

        $videos = Video::factory()->count($expected + 5)->create([
            'created_at' => fn() => $this->faker->dateTime,
        ])->sortByDesc('created_at')->values();

        $meta = [
            'from' => $videos[1]->getRouteKey(),
            'hasMore' => true,
            'perPage' => $expected,
            'to' => $videos[$expected]->getRouteKey(),
        ];

        $links = $this->createLinks($videos[1], $videos[$expected], $expected);

        $page = $this->videos->newQuery()->paginate(['after' => $videos[0]->getRouteKey()]);

        $this->assertSame(['page' => $meta], $page->meta());
        $this->assertSame($links, $page->links()->toArray());
        $this->assertPage($videos->skip(1)->take($expected), $page);
    }

    public function testItCanRemoveMeta(): void
    {
        $this->paginator->withoutMeta();

        $videos = Video::factory()->count(4)->create([
            'created_at' => fn() => $this->faker->dateTime,
        ])->sortByDesc('created_at')->values();

        $links = $this->createLinks($videos[0], $videos[2], 3);

        $page = $this->videos->newQuery()->paginate(['limit' => '3']);

        $this->assertEmpty($page->meta());
        $this->assertSame($links, $page->links()->toArray());
        $this->assertPage($videos->take(3), $page);
    }

    /**
     * @param Model $from
     * @param Model $to
     * @param int $limit
     * @return array
     */
    private function createLinks(Model $from, Model $to, int $limit): array
    {
        return [
            'first' => [
                'href' => 'http://localhost/api/v1/videos?' . Arr::query([
                    'page' => [
                        'limit' => $limit,
                    ],
                ]),
            ],
            'next' => [
                'href' => 'http://localhost/api/v1/videos?' . Arr::query([
                        'page' => [
                            'after' => $to->getRouteKey(),
                            'limit' => $limit,
                        ],
                    ]),
            ],
            'prev' => [
                'href' => 'http://localhost/api/v1/videos?' . Arr::query([
                    'page' => [
                        'before' => $from->getRouteKey(),
                        'limit' => $limit,
                    ],
                ]),
            ],
        ];
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
