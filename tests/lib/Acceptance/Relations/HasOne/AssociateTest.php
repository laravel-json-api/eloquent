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

    public function testPhoneToNullKeepsModel(): void
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

    public function testPhoneToNullDeletesPhone(): void
    {
        $this->schema->relationship('phone')->deleteDetachedModel();

        $user = User::factory()->create();
        $phone = Phone::factory(['user_id' => $user])->create();

        $actual = $this->repository->modifyToOne($user, 'phone')->associate(null);

        $this->assertNull($actual);
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

    public function testPhoneToPhoneDeletesOriginalPhone(): void
    {
        $this->schema->relationship('phone')->deleteDetachedModel();

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

        $this->assertDatabaseMissing('phones', [
            $existing->getKeyName() => $existing->getKey(),
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
