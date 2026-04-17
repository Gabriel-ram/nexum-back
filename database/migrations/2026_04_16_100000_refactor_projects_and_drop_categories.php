<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Refactorizar tabla projects
        Schema::table('projects', function (Blueprint $table) {
            $table->boolean('archived')->default(false)->after('project_url');
            $table->dropForeign(['category_id']);
            $table->dropColumn(['technologies', 'category_id']);
        });

        // 2. Tabla pivote project_skills (many-to-many)
        Schema::create('project_skills', function (Blueprint $table) {
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->primary(['project_id', 'skill_id']);
        });

        // 3. Eliminar tabla categories (su contenido pasa a skills como type='project_category')
        Schema::dropIfExists('categories');
    }

    public function down(): void
    {
        Schema::dropIfExists('project_skills');

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('slug', 100)->unique();
            $table->timestamps();
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('archived');
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->json('technologies')->nullable();
        });
    }
};
