<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_id')
                  ->constrained('projects')
                  ->cascadeOnDelete();

            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            $table->string('title', 255);
            $table->text('description')->nullable();

            $table->enum('status', ['pending', 'in_progress', 'completed'])
                  ->default('pending');

            $table->enum('priority', ['low', 'medium', 'high'])
                  ->default('medium');

            $table->date('due_date')->nullable();
            $table->integer('position')->default(0);
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            // فهارس للأداء
            $table->index(['user_id', 'status']);
            $table->index('project_id');
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};