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

namespace LaravelJsonApi\Eloquent\Tests\Acceptance\Relations\HasOne;

use App\Models\Phone;
use App\Models\User;
use App\Schemas\PhoneSchema;

class AssociateTest extends TestCase
{

    public function testNullToPhone(): void
    {
        $user = User::factory()->create();
        $phone = Phone::factory()->create(['user_id' => null]);

        $actual = $this->repository->modifyToOne($user, 'phone')->associate([
            'type' => 'phones',
            'id' => (string) $phone->getRouteKey(),
        ]);

        $this->assertTrue($phone->is($actual));
        $this->assertTrue($user->relationLoaded('phone'));
        $this->assertTrue($phone->is($user->getRelation('phone')));

        $this->assertDatabaseHas('phones', [
            'id' => $phone->getKey(),
            'user_id' => $user->getKey(),
        ]);
    }

    public function testPhoneToNull(): void
    {
        $user = User::factory()->create();
        $phone = Phone::factory(['user_id' => $user])->create();

        $actual = $this->repository->modifyToOne($user, 'phone')->associate(null);

        $this->assertNull($actual);
        $this->assertTrue($user->relationLoaded('phone'));
        $this->assertNull($user->getRelation('phone'));

        $this->assertDatabaseHas('phones', [
            'id' => $phone->getKey(),
            'user_id' => null,
        ]);
    }

    public function testPhoneToPhone(): void
    {
        $user = User::factory()->create();
        $existing = Phone::factory()->create(['user_id' => $user]);
        $phone = Phone::factory()->create();

        $actual = $this->repository->modifyToOne($user, 'phone')->associate([
            'type' => 'phones',
            'id' => (string) $phone->getRouteKey(),
        ]);

        $this->assertTrue($phone->is($actual));
        $this->assertTrue($user->relationLoaded('phone'));
        $this->assertTrue($phone->is($user->getRelation('phone')));

        $this->assertDatabaseHas('phones', [
            'id' => $phone->getKey(),
            'user_id' => $user->getKey(),
        ]);

        $this->assertDatabaseHas('phones', [
            'id' => $existing->getKey(),
            'user_id' => null,
        ]);
    }

    public function testWithIncludePaths(): void
    {
        $user = User::factory()->create();
        $phone = Phone::factory()->create(['user_id' => null]);

        $actual = $this->repository->modifyToOne($user, 'phone')->with('user')->associate([
            'type' => 'phones',
            'id' => (string) $phone->getRouteKey(),
        ]);

        $this->assertTrue($phone->is($actual));
        $this->assertTrue($actual->relationLoaded('user'));
        $this->assertTrue($user->is($actual->getRelation('user')));
    }

    public function testWithDefaultEagerLoading(): void
    {
        $this->createSchemaWithDefaultEagerLoading(PhoneSchema::class, 'user');

        $user = User::factory()->create();
        $phone = Phone::factory()->create(['user_id' => null]);

        $actual = $this->repository->modifyToOne($user, 'phone')->associate([
            'type' => 'phones',
            'id' => (string) $phone->getRouteKey(),
        ]);

        $this->assertTrue($phone->is($actual));
        $this->assertTrue($actual->relationLoaded('user'));
        $this->assertTrue($user->is($actual->getRelation('user')));
    }
}
