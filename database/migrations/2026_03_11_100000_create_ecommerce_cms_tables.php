<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pages CMS (Accueil, À propos, Contact, FAQ, etc.)
        if (!Schema::hasTable('ecommerce_cms_pages')) {
            Schema::create('ecommerce_cms_pages', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->unsignedBigInteger('shop_id')->index();
                $table->string('title');
                $table->string('slug')->index();
                $table->longText('content')->nullable();
                $table->string('image_path')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamp('published_at')->nullable();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['shop_id', 'slug']);
            });
        }

        // Bannières
        if (!Schema::hasTable('ecommerce_cms_banners')) {
            Schema::create('ecommerce_cms_banners', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->unsignedBigInteger('shop_id')->index();
                $table->string('title');
                $table->string('image_path')->nullable();
                $table->string('link')->nullable();
                $table->string('position', 50)->default('homepage'); // homepage, promotion, slider
                $table->boolean('is_active')->default(true)->index();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // Catégories blog
        if (!Schema::hasTable('ecommerce_cms_blog_categories')) {
            Schema::create('ecommerce_cms_blog_categories', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->unsignedBigInteger('shop_id')->index();
                $table->string('name');
                $table->string('slug')->index();
                $table->text('description')->nullable();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();

                $table->unique(['shop_id', 'slug']);
            });
        }

        // Articles / Blog
        if (!Schema::hasTable('ecommerce_cms_blog_articles')) {
            Schema::create('ecommerce_cms_blog_articles', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->unsignedBigInteger('shop_id')->index();
                $table->uuid('category_id')->nullable()->index();
                $table->string('title');
                $table->string('slug')->index();
                $table->longText('content')->nullable();
                $table->string('image_path')->nullable();
                $table->string('excerpt', 500)->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamp('published_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['shop_id', 'slug']);
            });
        }

        // Médias
        if (!Schema::hasTable('ecommerce_cms_media')) {
            Schema::create('ecommerce_cms_media', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->unsignedBigInteger('shop_id')->index();
                $table->string('name');
                $table->string('file_path');
                $table->string('file_type', 50)->nullable(); // image, document, etc.
                $table->unsignedBigInteger('file_size')->nullable();
                $table->string('mime_type', 100)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_cms_blog_articles');
        Schema::dropIfExists('ecommerce_cms_blog_categories');
        Schema::dropIfExists('ecommerce_cms_banners');
        Schema::dropIfExists('ecommerce_cms_pages');
        Schema::dropIfExists('ecommerce_cms_media');
    }
};
