<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('tenant')->after('email_verified_at');
            $table->timestamp('disabled_at')->nullable()->after('remember_token');

            $table->index('role');
            $table->index('disabled_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropIndex(['disabled_at']);
            $table->dropColumn(['role', 'disabled_at']);
        });
    }
};

