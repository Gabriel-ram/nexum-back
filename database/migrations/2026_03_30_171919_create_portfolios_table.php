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
        Schema::create('portfolios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('profession', 255)->nullable();
            $table->text('biography')->nullable();
            $table->string('phone', 100)->nullable();
            $table->string('location', 255)->nullable();
            $table->string('profile_image_path', 255)->nullable();
            $table->string('banner_image_path', 255)->nullable();
            $table->string('design_pattern', 50)->nullable();
            $table->string('global_privacy', 50)->default('public');
            $table->integer('views_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portfolios');
    }
};
