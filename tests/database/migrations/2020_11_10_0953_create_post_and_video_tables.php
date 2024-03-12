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
            $table->softDeletes();
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

        Schema::create('image_post', function (Blueprint $table) {
            $table->unsignedBigInteger('image_id');
            $table->unsignedBigInteger('post_id');
            $table->primary(['image_id', 'post_id']);

            $table->foreign('image_id')
                ->references('id')
                ->on('images')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('post_id')
                ->references('id')
                ->on('posts')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });

        Schema::create('post_video', function (Blueprint $table) {
            $table->unsignedBigInteger('post_id');
            $table->uuid('video_uuid');
            $table->primary(['post_id', 'video_uuid']);

            $table->foreign('post_id')
                ->references('id')
                ->on('posts')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('video_uuid')
                ->references('uuid')
                ->on('videos')
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
        Schema::dropIfExists('post_video');
        Schema::dropIfExists('image_post');
        Schema::dropIfExists('taggables');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('images');
        Schema::dropIfExists('comments');
        Schema::dropIfExists('videos');
        Schema::dropIfExists('posts');
    }
}
