<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_experiences', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('verification_url');
        });
    }

    public function down(): void
    {
        Schema::table('work_experiences', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
