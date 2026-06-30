<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('social_media_links')) {
            Schema::create('social_media_links', function (Blueprint $table): void {
                $table->id();
                $table->string('platform');
                $table->string('icon', 50);
                $table->string('url', 2048);
                $table->boolean('enabled')->default(true);
                $table->unsignedInteger('sort')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('social_media_links');
    }
};
