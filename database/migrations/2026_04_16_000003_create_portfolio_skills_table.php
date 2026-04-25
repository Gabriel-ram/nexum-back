<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portfolio_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portfolio_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->string('level', 20)->nullable(); // basic | intermediate | advanced (null for soft skills)
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['portfolio_id', 'skill_id']); // one record per skill per portfolio (soft delete pattern)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_skills');
    }
};
