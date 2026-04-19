<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_experience_skills', function (Blueprint $table) {
            $table->foreignId('work_experience_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->primary(['work_experience_id', 'skill_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_experience_skills');
    }
};
