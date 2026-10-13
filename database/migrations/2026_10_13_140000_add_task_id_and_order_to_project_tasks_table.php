<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El código de subtareas (ProjectTask::subtasks, ProjectTaskController@index)
 * y ProjectTaskResource ('order') ya usan estas columnas, pero nunca se
 * crearon en una migración. Se agregan protegiendo con hasColumn por si
 * la base de datos de producción ya las tiene creadas a mano.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('project_tasks', 'task_id')) {
            Schema::table('project_tasks', function (Blueprint $table) {
                $table->foreignId('task_id')
                    ->nullable()
                    ->after('project_id')
                    ->constrained('project_tasks')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('project_tasks', 'order')) {
            Schema::table('project_tasks', function (Blueprint $table) {
                $table->unsignedInteger('order')->default(0)->after('task_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('project_tasks', 'order')) {
            Schema::table('project_tasks', function (Blueprint $table) {
                $table->dropColumn('order');
            });
        }

        if (Schema::hasColumn('project_tasks', 'task_id')) {
            Schema::table('project_tasks', function (Blueprint $table) {
                $table->dropForeign(['task_id']);
                $table->dropColumn('task_id');
            });
        }
    }
};
