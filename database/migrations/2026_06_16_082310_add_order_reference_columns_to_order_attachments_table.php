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
        if (! Schema::hasColumn('order_attachments', 'order_id')) {
            Schema::table('order_attachments', function (Blueprint $table) {
                $table->foreignId('order_id')
                    ->after('id')
                    ->constrained('orders')
                    ->cascadeOnDelete()
                    ->cascadeOnUpdate();
            });
        }

        if (! Schema::hasColumn('order_attachments', 'disk')) {
            Schema::table('order_attachments', function (Blueprint $table) {
                $table->string('disk')->default('public')->after('order_id');
            });
        }

        if (! Schema::hasColumn('order_attachments', 'file_path')) {
            Schema::table('order_attachments', function (Blueprint $table) {
                $table->string('file_path')->after('disk');
            });
        }

        if (! Schema::hasColumn('order_attachments', 'file_mime')) {
            Schema::table('order_attachments', function (Blueprint $table) {
                $table->string('file_mime')->nullable()->after('file_path');
            });
        }

        if (! Schema::hasColumn('order_attachments', 'original_name')) {
            Schema::table('order_attachments', function (Blueprint $table) {
                $table->string('original_name')->after('file_mime');
            });
        }

        if (! Schema::hasColumn('order_attachments', 'size_bytes')) {
            Schema::table('order_attachments', function (Blueprint $table) {
                $table->unsignedBigInteger('size_bytes')->default(0)->after('original_name');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_attachments', function (Blueprint $table) {
            if (Schema::hasColumn('order_attachments', 'order_id')) {
                $table->dropConstrainedForeignId('order_id');
            }

            $dropColumns = [];
            foreach (['disk', 'file_path', 'file_mime', 'original_name', 'size_bytes'] as $column) {
                if (Schema::hasColumn('order_attachments', $column)) {
                    $dropColumns[] = $column;
                }
            }

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
