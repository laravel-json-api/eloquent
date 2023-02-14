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

namespace LaravelJsonApi\Eloquent\Tests\Acceptance\Relations\HasOneThrough;

use App\Models\Car;
use App\Models\CarOwner;
use App\Models\Mechanic;
use App\Schemas\CarOwnerSchema;
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
        $this->repository = $this->schemas()->schemaFor('mechanics')->repository();
    }

    public function test(): void
    {
        $mechanic = Mechanic::factory()->create();

        $owner = CarOwner::factory()->for(
            Car::factory(['mechanic_id' => $mechanic])
        )->create();

        $actual = $this->repository
            ->queryToOne($mechanic, 'carOwner')
            ->first();

        $this->assertTrue($owner->is($actual));
    }

    public function testWithIncludePaths(): void
    {
        $mechanic = Mechanic::factory()->create();

        $owner = CarOwner::factory()->for(
            Car::factory(['mechanic_id' => $mechanic])
        )->create();

        $actual = $this->repository
            ->queryToOne($mechanic, 'carOwner')
            ->with('car')
            ->first();

        $this->assertTrue($owner->is($actual));
        $this->assertTrue($actual->relationLoaded('car'));
    }

    public function testWithDefaultEagerLoading(): void
    {
        $this->createSchemaWithDefaultEagerLoading(CarOwnerSchema::class, 'car');

        $mechanic = Mechanic::factory()->create();

        $owner = CarOwner::factory()->for(
            Car::factory(['mechanic_id' => $mechanic])
        )->create();

        $actual = $this->repository
            ->queryToOne($mechanic, 'carOwner')
            ->first();

        $this->assertTrue($owner->is($actual));
        $this->assertTrue($actual->relationLoaded('car'));
    }

    public function testWithFilter(): void
    {
        $mechanic = Mechanic::factory()->create();

        $owner = CarOwner::factory()->for(
            Car::factory(['mechanic_id' => $mechanic])
        )->create(['name' => 'John Doe']);

        $actual = $this->repository
            ->queryToOne($mechanic, 'carOwner')
            ->filter(['name' => 'Doe'])
            ->first();

        $this->assertTrue($owner->is($actual));
    }

    public function testWithFilterReturnsNull(): void
    {
        $mechanic = Mechanic::factory()->create();

        CarOwner::factory()->for(
            Car::factory(['mechanic_id' => $mechanic])
        )->create(['name' => 'John Doe']);

        $actual = $this->repository
            ->queryToOne($mechanic, 'carOwner')
            ->filter(['name' => 'Jane'])
            ->first();

        $this->assertNull($actual);
    }

    public function testEmpty(): void
    {
        $mechanic = Mechanic::factory()->create();

        $this->assertNull($this->repository->queryToOne($mechanic, 'carOwner')->first());
    }

    /**
     * If the relation is already loaded and there are no filters, the already
     * loaded model should be returned rather than executing a fresh query.
     */
    public function testAlreadyLoaded(): void
    {
        $mechanic = Mechanic::factory()->create();

        CarOwner::factory()->for(
            Car::factory(['mechanic_id' => $mechanic])
        )->create();

        $expected = $mechanic->carOwner;

        $actual = $this->repository
            ->queryToOne($mechanic, 'carOwner')
            ->first();

        $this->assertSame($expected, $actual);
        $this->assertEmpty($actual->getRelations());
    }

    public function testAlreadyLoadedWithIncludePaths(): void
    {
        $mechanic = Mechanic::factory()->create();

        $owner = CarOwner::factory()->for(
            Car::factory(['mechanic_id' => $mechanic])
        )->create();

        $expected = $mechanic->carOwner;

        $this->assertFalse($expected->relationLoaded('car'));

        $actual = $this->repository
            ->queryToOne($mechanic, 'carOwner')
            ->with('car')
            ->first();

        $this->assertSame($expected, $actual);
        $this->assertTrue($actual->relationLoaded('car'));
        $this->assertTrue($owner->car->is($actual->car));
    }

    /**
     * If a filter is used when the relation is already loaded, we do need to
     * execute a database query to determine if the model matches the filters.
     */
    public function testAlreadyLoadedWithFilter(): void
    {
        $mechanic = Mechanic::factory()->create();

        CarOwner::factory()->for(
            Car::factory(['mechanic_id' => $mechanic])
        )->create(['name' => 'John Doe']);

        $expected = $mechanic->carOwner;

        $actual = $this->repository
            ->queryToOne($mechanic, 'carOwner')
            ->filter(['name' => 'Doe'])
            ->first();

        $this->assertNotSame($expected, $actual);
        $this->assertTrue($expected->is($actual));
    }

}
