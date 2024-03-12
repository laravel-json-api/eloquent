<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Mechanic extends Model
{

    use HasFactory;

    /**
     * @var string[]
     */
    protected $fillable = ['name'];

    /**
     * @return HasOneThrough
     */
    public function carOwner(): HasOneThrough
    {
        return $this->hasOneThrough(CarOwner::class, Car::class);
    }
}
