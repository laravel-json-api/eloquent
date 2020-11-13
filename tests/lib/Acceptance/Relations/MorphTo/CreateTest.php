<?php
/*
 * Copyright 2020 Cloud Creativity Limited
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

namespace LaravelJsonApi\Eloquent\Tests\Acceptance\Relations\MorphTo;

use App\Models\User;

class CreateTest extends TestCase
{

    public function test(): void
    {
        $user = User::factory()->create();

        $image = $this->repository->create()->store([
            'url' => 'http://example.com/images/image01.png',
            'imageable' => [
                'type' => 'users',
                'id' => (string) $user->getRouteKey(),
            ],
        ]);

        $this->assertTrue($image->relationLoaded('imageable'));
        $this->assertTrue($user->is($image->getRelation('imageable')));

        $this->assertDatabaseHas('images', [
            'id' => $image->getKey(),
            'imageable_id' => $user->getKey(),
            'imageable_type' => User::class,
            'url' => 'http://example.com/images/image01.png',
        ]);
    }

    public function testNull(): void
    {
        $image = $this->repository->create()->store([
            'url' => 'http://example.com/images/image01.png',
            'imageable' => null,
        ]);

        $this->assertTrue($image->relationLoaded('imageable'));
        $this->assertNull($image->getRelation('imageable'));

        $this->assertDatabaseHas('images', [
            'id' => $image->getKey(),
            'imageable_id' => null,
            'imageable_type' => null,
            'url' => 'http://example.com/images/image01.png',
        ]);
    }
}
