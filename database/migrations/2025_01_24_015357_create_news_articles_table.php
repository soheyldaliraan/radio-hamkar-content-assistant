<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('news_articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('original_content');
            $table->text('summary');
            $table->string('source_name');
            $table->string('source_url');
            $table->string('category');
            $table->timestamp('published_at');
            $table->integer('relevance_score');
            $table->text('linkedin_post');
            $table->text('generated_image_url');
            $table->string('approval_status')->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news_articles');
    }
};
