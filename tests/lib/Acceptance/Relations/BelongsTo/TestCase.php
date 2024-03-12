<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Eloquent\Tests\Acceptance\Relations\BelongsTo;

use LaravelJsonApi\Eloquent\Repository;
use LaravelJsonApi\Eloquent\Tests\Acceptance\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{

    /**
     * @var Repository
     */
    protected Repository $repository;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->schemas()->schemaFor('phones')->repository();
    }
}
