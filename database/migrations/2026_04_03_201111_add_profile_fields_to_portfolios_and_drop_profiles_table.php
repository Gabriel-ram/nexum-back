<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portfolios', function (Blueprint $table) {
            $table->string('avatar_path')->nullable()->after('location');
            $table->string('linkedin_url')->nullable()->after('avatar_path');
            $table->string('github_url')->nullable()->after('linkedin_url');
        });

        Schema::dropIfExists('profiles');
    }

    public function down(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('profession')->nullable();
            $table->text('bio')->nullable();
            $table->string('avatar_path')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('github_url')->nullable();
            $table->timestamps();
        });

        Schema::table('portfolios', function (Blueprint $table) {
            $table->dropColumn(['avatar_path', 'linkedin_url', 'github_url']);
        });
    }
};
