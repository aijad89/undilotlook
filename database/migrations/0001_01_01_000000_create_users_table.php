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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('phone')->nullable();

            // The participant's IC. User::icHash() turns it into the code printed
            // on the lot slip and shown on the vote page, so an account with no IC
            // simply has no code — see the model for why that code is an
            // identifier and not a secret.
            $table->string('ic_number')->nullable();

            // The office's community ID for this participant. Free text and not
            // unique: the office has not said whether IDs are ever shared.
            $table->string('community_id', 50)->nullable();

            // Accounts are created by an admin, never self-registered, so there is
            // no verification step and no `email_verified_at`. Switching an account
            // off is what `is_active` is for, and EnforceUserIsActive turns that
            // into an immediate logout rather than a quiet half-session.
            $table->boolean('is_active')->default(true);

            $table->timestamp('last_seen_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
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
