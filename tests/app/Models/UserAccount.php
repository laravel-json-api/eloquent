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

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use LaravelJsonApi\Eloquent\Proxy;

class UserAccount extends Proxy implements Scope
{

    /**
     * UserAccount constructor.
     *
     * @param User|null $user
     */
    public function __construct(?User $user = null)
    {
        parent::__construct($user ?: new User());
    }

    /**
     * @inheritDoc
     */
    public function apply(Builder $builder, Model $model)
    {
        return $builder->whereNotNull(
            $model->qualifyColumn('email_verified_at')
        );
    }

}
