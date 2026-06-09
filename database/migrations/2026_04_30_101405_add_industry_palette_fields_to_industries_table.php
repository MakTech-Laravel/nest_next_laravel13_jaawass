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
        Schema::table('industries', function (Blueprint $table) {
            $table->string('title_color', 7)->nullable()->after('color');
            $table->string('desc_color', 7)->nullable()->after('title_color');
            $table->string('btn_color', 7)->nullable()->after('desc_color');
            $table->string('supplier_color', 7)->nullable()->after('btn_color');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('industries', function (Blueprint $table) {
            $table->dropColumn([
                'title_color',
                'desc_color',
                'btn_color',
                'supplier_color',
            ]);
        });
    }
};
