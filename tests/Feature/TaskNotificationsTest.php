<?php

use App\Enums\TaskReminderType;
use App\Mail\TaskAssigned;
use App\Mail\TaskReminder;
use App\Mail\WeeklyTaskReport;
use App\Mail\WeeklyTaskSummary;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;
use App\Services\TaskNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();
});

// ─── Correo al crear / asignar / reasignar ───────────────────────────

test('al crear una tarea asignada se encola el correo al responsable', function () {
    $creator = User::factory()->create();
    $assignee = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $creator->id]);

    Passport::actingAs($creator);

    $this->postJson("/api/projects/{$project->id}/tasks", [
        'title' => 'Preparar la propuesta comercial',
        'description' => "Alcance completo del proyecto.\nSegunda línea de la descripción.",
        'status' => 'pending',
        'due_date' => now()->addDays(2)->toDateTimeString(),
        'assigned_to' => $assignee->id,
        'priority' => 'high',
    ])->assertOk();

    Mail::assertQueued(TaskAssigned::class, fn (TaskAssigned $mail) => $mail->hasTo($assignee->email) && $mail->reassigned === false);
});

test('al crear una tarea sin responsable no se envía correo de asignación', function () {
    $creator = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $creator->id]);

    Passport::actingAs($creator);

    $this->postJson("/api/projects/{$project->id}/tasks", [
        'title' => 'Tarea sin asignar',
        'status' => 'pending',
    ])->assertOk();

    Mail::assertNotQueued(TaskAssigned::class);
});

test('al reasignar una tarea se avisa al nuevo responsable', function () {
    $creator = User::factory()->create();
    $previous = User::factory()->create();
    $newAssignee = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $creator->id]);
    $task = ProjectTask::factory()->create([
        'project_id' => $project->id,
        'created_by' => $creator->id,
        'assigned_to' => $previous->id,
    ]);

    Passport::actingAs($creator);

    $this->putJson("/api/projects/{$project->id}/tasks/{$task->id}", [
        'assigned_to' => $newAssignee->id,
    ])->assertOk();

    Mail::assertQueued(TaskAssigned::class, fn (TaskAssigned $mail) => $mail->hasTo($newAssignee->email) && $mail->reassigned === true);
});

test('si el responsable no cambia no se envía correo', function () {
    $creator = User::factory()->create();
    $assignee = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $creator->id]);
    $task = ProjectTask::factory()->create([
        'project_id' => $project->id,
        'created_by' => $creator->id,
        'assigned_to' => $assignee->id,
    ]);

    Passport::actingAs($creator);

    $this->putJson("/api/projects/{$project->id}/tasks/{$task->id}", [
        'title' => 'Título actualizado',
    ])->assertOk();

    Mail::assertNotQueued(TaskAssigned::class);
});

test('no se avisa cuando alguien se asigna la tarea a sí mismo', function () {
    $user = User::factory()->create();
    $task = ProjectTask::factory()->create([
        'assigned_to' => $user->id,
        'created_by' => $user->id,
    ]);

    $sent = app(TaskNotificationService::class)->notifyTaskAssigned($task, $user);

    expect($sent)->toBeFalse();
    Mail::assertNotQueued(TaskAssigned::class);
});

test('el correo de asignación usa el layout maestro y trae la descripción completa', function () {
    $creator = User::factory()->create(['name' => 'Carlos Ruiz']);
    $assignee = User::factory()->create(['name' => 'Ana María']);
    $project = Project::factory()->create(['name' => 'Proyecto Alfa']);
    $task = ProjectTask::factory()->create([
        'project_id' => $project->id,
        'created_by' => $creator->id,
        'assigned_to' => $assignee->id,
        'title' => 'Diseñar la portada',
        'description' => "Primera línea de la descripción.\nSegunda línea con todo el detalle.",
    ]);

    $html = (new TaskAssigned($task, $creator->name))->render();

    expect($html)
        ->toContain('Diseñar la portada')
        ->toContain('Primera línea de la descripción.')
        ->toContain('Segunda línea con todo el detalle.')
        ->toContain('Proyecto Alfa')
        ->toContain('Carlos Ruiz')
        // Huella del layout único (header/footer de appsline).
        ->toContain(config('appsline.name'));
});

// ─── Recordatorios por cron ──────────────────────────────────────────

test('el recordatorio de pendientes agrupa las tareas abiertas por usuario', function () {
    $ana = User::factory()->create(['name' => 'Ana']);
    ProjectTask::factory()->overdue()->create(['assigned_to' => $ana->id]);
    ProjectTask::factory()->create(['assigned_to' => $ana->id, 'due_date' => now()->addDays(4)]);
    ProjectTask::factory()->completed()->create(['assigned_to' => $ana->id]);

    $this->artisan('tasks:notify-pending')->assertExitCode(0);

    Mail::assertQueued(TaskReminder::class, fn (TaskReminder $mail) => $mail->hasTo($ana->email)
        && $mail->type === TaskReminderType::Pending
        && $mail->tasks->count() === 2);
});

test('el comando de tareas que vencen hoy solo avisa de las de hoy', function () {
    $ana = User::factory()->create();
    $luis = User::factory()->create();
    ProjectTask::factory()->dueToday()->create(['assigned_to' => $ana->id]);
    ProjectTask::factory()->create(['assigned_to' => $luis->id, 'due_date' => now()->addDays(5)]);
    ProjectTask::factory()->overdue()->create(['assigned_to' => $luis->id]);
    ProjectTask::factory()->dueToday()->completed()->create(['assigned_to' => $luis->id]);

    $this->artisan('tasks:notify-due-today')->assertExitCode(0);

    expect(Mail::queued(TaskReminder::class))->toHaveCount(1);
    Mail::assertQueued(TaskReminder::class, fn (TaskReminder $mail) => $mail->hasTo($ana->email)
        && $mail->type === TaskReminderType::DueToday
        && $mail->tasks->count() === 1);
});

test('el comando de vencidas excluye las de hoy y las completadas', function () {
    $ana = User::factory()->create();
    ProjectTask::factory()->overdue()->create(['assigned_to' => $ana->id]);
    ProjectTask::factory()->dueToday()->create(['assigned_to' => $ana->id]);
    ProjectTask::factory()->overdue()->completed()->create([
        'assigned_to' => $ana->id,
        'completed_at' => now(),
    ]);

    $this->artisan('tasks:notify-overdue')->assertExitCode(0);

    expect(Mail::queued(TaskReminder::class))->toHaveCount(1);
    Mail::assertQueued(TaskReminder::class, fn (TaskReminder $mail) => $mail->hasTo($ana->email)
        && $mail->type === TaskReminderType::Overdue
        && $mail->tasks->count() === 1);
});

test('el modo --dry-run no envía ningún correo', function () {
    $ana = User::factory()->create();
    ProjectTask::factory()->dueToday()->create(['assigned_to' => $ana->id]);
    ProjectTask::factory()->overdue()->create(['assigned_to' => $ana->id]);

    $this->artisan('tasks:notify-due-today', ['--dry-run' => true])->assertExitCode(0);
    $this->artisan('tasks:notify-overdue', ['--dry-run' => true])->assertExitCode(0);
    $this->artisan('tasks:notify-pending', ['--dry-run' => true])->assertExitCode(0);

    expect(Mail::queued(TaskReminder::class))->toHaveCount(0);
});

// ─── Reporte semanal ─────────────────────────────────────────────────

test('el reporte semanal resume completadas y vencidas por usuario', function () {
    Config::set('tasks.notifications.weekly_report_recipients', ['jefatura@appsline.com.mx']);

    $ana = User::factory()->create();
    // Completada esta semana, a tiempo.
    ProjectTask::factory()->completed()->create([
        'assigned_to' => $ana->id,
        'due_date' => now()->addDay(),
        'completed_at' => now(),
    ]);
    // Completada esta semana, tarde (venció hace 3 días).
    ProjectTask::factory()->completed()->create([
        'assigned_to' => $ana->id,
        'due_date' => now()->subDays(3),
        'completed_at' => now(),
    ]);
    // Sigue vencida.
    ProjectTask::factory()->overdue()->create(['assigned_to' => $ana->id]);
    // Completada la semana pasada: no debe contar.
    ProjectTask::factory()->completed()->create([
        'assigned_to' => $ana->id,
        'due_date' => now()->subDays(10),
        'completed_at' => now()->subWeek()->subDay(),
    ]);

    $data = app(TaskNotificationService::class)->weeklyReportData();

    expect($data['totals']['completed'])->toBe(2)
        ->and($data['totals']['on_time'])->toBe(1)
        ->and($data['totals']['late'])->toBe(1)
        ->and($data['totals']['overdue'])->toBe(1)
        ->and($data['totals']['users'])->toBe(1);

    $this->artisan('tasks:weekly-report')->assertExitCode(0);

    Mail::assertQueued(WeeklyTaskReport::class, fn (WeeklyTaskReport $mail) => $mail->hasTo($ana->email)
        && $mail->completed->count() === 2
        && $mail->overdue->count() === 1);
    Mail::assertQueued(WeeklyTaskSummary::class, fn (WeeklyTaskSummary $mail) => $mail->hasTo('jefatura@appsline.com.mx'));
});
