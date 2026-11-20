<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_members', function (Blueprint $table) {
            $table->enum('status', ['pending', 'accepted', 'rejected'])
                  ->default('pending')
                  ->after('role');

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('project_members', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'status']);
            $table->dropColumn('status');
        });
    }
};