<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
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
