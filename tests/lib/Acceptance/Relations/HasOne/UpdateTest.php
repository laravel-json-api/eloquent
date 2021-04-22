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

class UpdateTest extends TestCase
{

    public function testNullToPhone(): void
    {
        $user = User::factory()->create();
        $phone = Phone::factory()->create(['user_id' => null]);

        $this->repository->update($user)->store([
            'phone' => [
                'type' => 'phones',
                'id' => (string) $phone->getRouteKey(),
            ],
        ]);

        $this->assertTrue($user->relationLoaded('phone'));
        $this->assertTrue($phone->is($user->getRelation('phone')));

        $this->assertDatabaseHas('phones', [
            'id' => $phone->getKey(),
            'user_id' => $user->getKey(),
        ]);
    }

    public function testPhoneToNullKeepsPhone(): void
    {
        $user = User::factory()->create();
        $phone = Phone::factory(['user_id' => $user])->create();

        $this->repository->update($user)->store([
            'phone' => null,
        ]);

        $this->assertTrue($user->relationLoaded('phone'));
        $this->assertNull($user->getRelation('phone'));

        $this->assertDatabaseHas('phones', [
            'id' => $phone->getKey(),
            'user_id' => null,
        ]);
    }

    public function testPhoneToNullDeletesPhone(): void
    {
        $this->schema->relationship('phone')->deleteDetachedModel();

        $user = User::factory()->create();
        $phone = Phone::factory(['user_id' => $user])->create();

        $this->repository->update($user)->store([
            'phone' => null,
        ]);

        $this->assertTrue($user->relationLoaded('phone'));
        $this->assertNull($user->getRelation('phone'));

        $this->assertDatabaseMissing('phones', [
            $phone->getKeyName() => $phone->getKey(),
        ]);
    }

    public function testPhoneToPhoneKeepsOriginalPhone(): void
    {
        $user = User::factory()->create();
        $existing = Phone::factory()->create(['user_id' => $user]);
        $phone = Phone::factory()->create();

        $this->repository->update($user)->store([
            'phone' => [
                'type' => 'phones',
                'id' => (string) $phone->getRouteKey(),
            ],
        ]);

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

    public function testPhoneToPhoneDeletesOriginalPhone(): void
    {
        $this->schema->relationship('phone')->deleteDetachedModel();

        $user = User::factory()->create();
        $existing = Phone::factory()->create(['user_id' => $user]);
        $phone = Phone::factory()->create();

        $this->repository->update($user)->store([
            'phone' => [
                'type' => 'phones',
                'id' => (string) $phone->getRouteKey(),
            ],
        ]);

        $this->assertTrue($user->relationLoaded('phone'));
        $this->assertTrue($phone->is($user->getRelation('phone')));

        $this->assertDatabaseHas('phones', [
            'id' => $phone->getKey(),
            'user_id' => $user->getKey(),
        ]);

        $this->assertDatabaseMissing('phones', [
            $existing->getKeyName() => $existing->getKey(),
        ]);
    }

}
