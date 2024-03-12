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
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{

    use HasFactory;

    /**
     * @var string[]
     */
    protected $fillable = ['name', 'permissions'];

    /**
     * @var string[]
     */
    protected $casts = [
        'permissions' => 'array',
    ];

    /**
     * @return BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this
            ->belongsToMany(User::class)
            ->withPivot('approved')
            ->withTimestamps();
    }
}
