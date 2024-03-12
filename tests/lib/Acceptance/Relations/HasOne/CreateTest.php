<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Eloquent\Tests\Acceptance\Relations\HasOne;

use App\Models\Phone;
use App\Models\User;

class CreateTest extends TestCase
{

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        User::creating(fn(User $user) => $user->password = 'secret');
    }

    public function test(): void
    {
        $phone = Phone::factory()->create(['user_id' => null]);

        $user = $this->repository->create()->store([
            'email' => 'john@example.com',
            'name' => 'John Doe',
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

    public function testNull(): void
    {
        $user = $this->repository->create()->store([
            'email' => 'john@example.com',
            'name' => 'John Doe',
            'phone' => null,
        ]);

        $this->assertTrue($user->relationLoaded('phone'));
        $this->assertNull($user->getRelation('phone'));
    }
}
