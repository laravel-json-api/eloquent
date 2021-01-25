<?php
/*
 * Copyright 2021 Cloud Creativity Limited
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

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePostAndVideoTables extends Migration
{

    /**
     * Execute the migration.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger('user_id');
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('content');
            $table->timestamp('published_at')->nullable();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });

        Schema::create('videos', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->timestamps();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('url');
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->nullableMorphs('commentable');
            $table->text('content');

            $table->foreign('user_id')
                ->references('id')
                ->on('posts')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });

        Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('url');
            $table->nullableMorphs('imageable');
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name');
        });

        Schema::create('taggables', function (Blueprint $table) {
            $table->unsignedBigInteger('tag_id');
            $table->morphs('taggable');
            $table->boolean('approved')->default(false);

            $table->primary([
                'tag_id',
                'taggable_id',
                'taggable_type',
            ]);

            $table->foreign('tag_id')
                ->references('id')
                ->on('tags')
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
        Schema::dropIfExists('images');
        Schema::dropIfExists('comments');
        Schema::dropIfExists('videos');
        Schema::dropIfExists('posts');
    }
}
