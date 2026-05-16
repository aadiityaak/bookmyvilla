<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('investor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type');
            $table->string('name');
            $table->string('address')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('draft');
            $table->json('gallery')->nullable();
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index('owner_id');
            $table->index('investor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
