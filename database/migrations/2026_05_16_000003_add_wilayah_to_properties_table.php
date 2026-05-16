<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->char('province_id', 2)->nullable()->after('address');
            $table->char('regency_id', 4)->nullable()->after('province_id');

            $table->index('province_id');
            $table->index('regency_id');
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropIndex(['province_id']);
            $table->dropIndex(['regency_id']);

            $table->dropColumn(['province_id', 'regency_id']);
        });
    }
};
