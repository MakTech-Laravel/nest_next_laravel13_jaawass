<?php

use App\Enums\UserRole;
use App\Enums\UserStatus;
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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();

            $table->string('role')->default(UserRole::BUYER->value);
            $table->string('status')->default(UserStatus::ACTIVE->value)->index();
            $table->boolean('agreed_to_terms')->default(false);
            $table->string('manufacture_status')->nullable()->index();
            $table->text('manufacture_status_reason')->nullable();
            $table->timestamp('manufacture_status_at')->nullable();
            $table->string('timezone')->nullable();

            // Notification Preferences
            $table->boolean('quote_notification')->default(false);
            $table->boolean('message_notification')->default(false);
            $table->boolean('supplier_update')->default(false);
            $table->boolean('weekly_digest')->default(false);
            $table->boolean('marketing_promotion')->default(false);

            // Account information
            $table->timestamp('deactivated_at')->nullable()->index();
            $table->timestamp('deleted_at')->nullable()->index();
            $table->boolean('is_permanently_deleted')->default(false)->index();
            $table->text('deactivated_reason')->nullable();
            $table->text('deleted_reason')->nullable();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
