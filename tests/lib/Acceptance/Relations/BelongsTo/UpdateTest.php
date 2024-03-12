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

use App\Models\Phone;
use App\Models\User;

class UpdateTest extends TestCase
{

    public function testNullToUser(): void
    {
        $user = User::factory()->create();
        $phone = Phone::factory()->create(['user_id' => null]);

        $this->repository->update($phone)->store([
            'user' => [
                'type' => 'users',
                'id' => (string) $user->getRouteKey(),
            ],
        ]);

        $this->assertTrue($phone->relationLoaded('user'));
        $this->assertTrue($user->is($phone->getRelation('user')));

        $this->assertDatabaseHas('phones', [
            'id' => $phone->getKey(),
            'user_id' => $user->getKey(),
        ]);
    }

    public function testUserToNull(): void
    {
        $user = User::factory()->create();
        $phone = Phone::factory(['user_id' => $user])->create();

        $this->repository->update($phone)->store([
            'user' => null,
        ]);

        $this->assertTrue($phone->relationLoaded('user'));
        $this->assertNull($phone->getRelation('user'));

        $this->assertDatabaseHas('phones', [
            'id' => $phone->getKey(),
            'user_id' => null,
        ]);
    }

    public function testUserToUser(): void
    {
        $existing = User::factory()->create();
        $phone = Phone::factory()->create(['user_id' => $existing]);
        $user = User::factory()->create();

        $this->repository->update($phone)->store([
            'user' => [
                'type' => 'users',
                'id' => (string) $user->getRouteKey(),
            ],
        ]);

        $this->assertTrue($phone->relationLoaded('user'));
        $this->assertTrue($user->is($phone->getRelation('user')));

        $this->assertDatabaseHas('phones', [
            'id' => $phone->getKey(),
            'user_id' => $user->getKey(),
        ]);
    }

}
