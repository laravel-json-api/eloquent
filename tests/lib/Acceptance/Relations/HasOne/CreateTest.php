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
