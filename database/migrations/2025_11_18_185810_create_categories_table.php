<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('parent_id')->nullable()->index();
            $table->foreign('parent_id')
                ->references('id')
                ->on('categories')
                ->onDelete('set null');

            $table->string('title')->index();
            $table->string('slug')->unique();
            $table->string('image')->nullable();
            $table->unsignedBigInteger('sort_order')->default(0);
            $table->text('content')->nullable();

            $table->string('meta_title')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->text('meta_desc')->nullable();

            $table->boolean('publish_status')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
