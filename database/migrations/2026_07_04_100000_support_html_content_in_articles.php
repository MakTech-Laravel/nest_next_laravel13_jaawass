<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE articles MODIFY content LONGTEXT NULL');
            DB::statement('ALTER TABLE article_translations MODIFY content LONGTEXT NULL');
        }

        Schema::table('articles', function (Blueprint $table) {
            if (! Schema::hasColumn('articles', 'content_format')) {
                $table->string('content_format')->default('html')->after('content');
            }
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            if (Schema::hasColumn('articles', 'content_format')) {
                $table->dropColumn('content_format');
            }
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE articles MODIFY content TEXT NULL');
            DB::statement('ALTER TABLE article_translations MODIFY content TEXT NULL');
        }
    }
};
