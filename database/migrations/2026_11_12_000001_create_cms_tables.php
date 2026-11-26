<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('role')->default('author');
            $t->boolean('active')->default(true);
        });
        Schema::create('categories', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->timestamps();
        });
        Schema::create('media', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained();
            $t->string('name');
            $t->string('path')->unique();
            $t->string('mime');
            $t->unsignedBigInteger('size');
            $t->string('alt')->default('');
            $t->boolean('is_public')->default(false);
            $t->timestamps();
        });
        Schema::create('contents', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained();
            $t->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('media_id')->nullable()->constrained('media')->nullOnDelete();
            $t->string('type')->default('post');
            $t->string('locale', 2)->default('ms');
            $t->string('title');
            $t->string('slug');
            $t->text('excerpt')->nullable();
            $t->longText('body');
            $t->string('tags')->nullable();
            $t->json('gallery_ids')->nullable();
            $t->string('status')->default('draft');
            $t->timestamp('published_at')->nullable();
            $t->string('seo_title')->nullable();
            $t->string('seo_description', 300)->nullable();
            $t->unsignedInteger('version')->default(1);
            $t->timestamps();
            $t->softDeletes();
            $t->unique(['locale', 'slug']);
            $t->index(['locale', 'status', 'published_at']);
        });
        Schema::create('revisions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('content_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained();
            $t->json('snapshot');
            $t->timestamps();
        });
        Schema::create('settings', function (Blueprint $t) {
            $t->string('key')->primary();
            $t->json('value');
            $t->timestamps();
        });
        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('action');
            $t->string('subject');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['audit_logs', 'settings', 'revisions', 'contents', 'media', 'categories'] as $table) {
            Schema::dropIfExists($table);
        } Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['role', 'active']));
    }
};
