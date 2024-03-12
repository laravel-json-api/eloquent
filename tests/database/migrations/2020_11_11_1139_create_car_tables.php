<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCarTables extends Migration
{

    /**
     * Execute the migration.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('mechanics', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name');
        });

        Schema::create('cars', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger('mechanic_id');
            $table->string('model');

            $table->foreign('mechanic_id')
                ->references('id')
                ->on('mechanics')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });

        Schema::create('car_owners', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger('car_id');
            $table->string('name');

            $table->foreign('car_id')
                ->references('id')
                ->on('cars')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('car_owners');
        Schema::dropIfExists('cars');
        Schema::dropIfExists('mechanics');
    }
}
