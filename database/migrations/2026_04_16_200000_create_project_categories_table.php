<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabla de categorías de proyecto (reemplaza el type='project_category' en skills)
        Schema::create('project_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->timestamps();
        });

        // 2. FK en projects apuntando a la nueva tabla
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('category_id')
                ->nullable()
                ->after('portfolio_id')
                ->constrained('project_categories')
                ->nullOnDelete();
        });

        // 3. Limpiar las skills que antes hacían de categoría de proyecto
        DB::table('skills')->where('type', 'project_category')->delete();
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });

        Schema::dropIfExists('project_categories');
    }
};
