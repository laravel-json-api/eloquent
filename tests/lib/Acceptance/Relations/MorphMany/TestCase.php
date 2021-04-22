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

namespace LaravelJsonApi\Eloquent\Tests\Acceptance\Relations\MorphMany;

use App\Models\Comment;
use App\Schemas\VideoSchema;
use LaravelJsonApi\Eloquent\Repository;
use LaravelJsonApi\Eloquent\Tests\Acceptance\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{

    /**
     * @var VideoSchema
     */
    protected VideoSchema $schema;

    /**
     * @var Repository
     */
    protected Repository $repository;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->schema = $this->schemas()->schemaFor('videos');
        $this->repository = $this->schema->repository();
    }

    /**
     * @param iterable $expected
     * @param iterable $actual
     * @return void
     */
    protected function assertComments(iterable $expected, iterable $actual): void
    {
        $expected = collect($expected)
            ->map($fn = fn(Comment $comment) => $comment->getKey())
            ->values()
            ->all();

        $actual = collect($actual)->map($fn)->values()->all();

        $this->assertSame($expected, $actual);
    }
}
