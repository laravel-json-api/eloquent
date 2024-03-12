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

use App\Models\User;

class CreateTest extends TestCase
{

    public function test(): void
    {
        $user = User::factory()->create();

        $phone = $this->repository->create()->store([
            'number' => '07777123456',
            'user' => [
                'type' => 'users',
                'id' => (string) $user->getRouteKey(),
            ],
        ]);

        $this->assertTrue($phone->relationLoaded('user'));
        $this->assertTrue($user->is($phone->getRelation('user')));

        $this->assertDatabaseHas('phones', [
            'id' => $phone->getKey(),
            'number' => '07777123456',
            'user_id' => $user->getKey(),
        ]);
    }

    public function testNull(): void
    {
        $phone = $this->repository->create()->store([
            'number' => '07777123456',
            'user' => null,
        ]);

        $this->assertTrue($phone->relationLoaded('user'));
        $this->assertNull($phone->getRelation('user'));

        $this->assertDatabaseHas('phones', [
            'id' => $phone->getKey(),
            'number' => '07777123456',
            'user_id' => null,
        ]);
    }
}
