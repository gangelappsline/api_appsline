<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;
use App\Http\Resources\TaskPerUserResource;

class DashboardController extends BaseController
{
    /**
     * Return multiple datasets used by the dashboard charts and widgets.
     * Cached briefly for performance.
     */
    public function index(Request $request)
    {
        $projectId = $request->query('project_id');
        $cacheKey = 'dashboard_overview_v1' . ($projectId ? "_project_{$projectId}" : '');
        $ttl = 60; // seconds

        $data = Cache::remember($cacheKey, $ttl, function () use ($projectId) {
            $now = Carbon::now();

            // Tasks by status (optionally filtered by project)
            $tasksByStatus = ProjectTask::when($projectId, fn($q) => $q->where('project_id', $projectId))
                ->select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->get()
                ->pluck('total', 'status');

            // Overall counts
            $totalTasks = ProjectTask::when($projectId, fn($q) => $q->where('project_id', $projectId))->count();
            $completedTasks = ProjectTask::when($projectId, fn($q) => $q->where('project_id', $projectId))->where('status', 'completed')->count();
            $inProgress = ProjectTask::when($projectId, fn($q) => $q->where('project_id', $projectId))->where('status', 'in_progress')->count();
            $pending = ProjectTask::when($projectId, fn($q) => $q->where('project_id', $projectId))->where('status', 'pending')->count();

            // Overdue tasks (incomplete and due_date < now)
            $overdueTasksQuery = ProjectTask::when($projectId, fn($q) => $q->where('project_id', $projectId))
                ->whereIn('status', ['pending','in_progress'])
                ->whereNotNull('due_date')
                ->where('due_date', '<', $now);
            $overdueTasks = $overdueTasksQuery->count();

            // Average completion time (hours) for completed tasks
            $avgCompletionHoursQuery = ProjectTask::when($projectId, fn($q) => $q->where('project_id', $projectId))
                ->whereNotNull('completed_at')
                ->whereColumn('completed_at', '>=', 'created_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, completed_at))/3600 as avg_hours');
            $avgCompletionHours = $avgCompletionHoursQuery->value('avg_hours');
            $avgCompletionHours = $avgCompletionHours ? round((float)$avgCompletionHours, 2) : null;

            // Completed on time vs late (only tasks with due_date)
            $completedOnTimeQuery = ProjectTask::when($projectId, fn($q) => $q->where('project_id', $projectId))
                ->whereNotNull('completed_at')
                ->whereNotNull('due_date')
                ->whereColumn('completed_at', '<=', 'due_date');
            $completedOnTime = $completedOnTimeQuery->count();

            $completedLateQuery = ProjectTask::when($projectId, fn($q) => $q->where('project_id', $projectId))
                ->whereNotNull('completed_at')
                ->whereNotNull('due_date')
                ->whereColumn('completed_at', '>', 'due_date');
            $completedLate = $completedLateQuery->count();

            // Tasks per user (top 10) — includes completed tasks only, on_time and late counts
            $tasksPerUserQuery = DB::table('project_tasks')
                ->join('users', 'users.id', '=', 'project_tasks.assigned_to')
                ->select(
                    'users.id',
                    'users.name',
                    'users.photo',
                    DB::raw('COUNT(project_tasks.id) as total_completed'),
                    DB::raw("SUM(CASE WHEN project_tasks.due_date IS NOT NULL AND project_tasks.completed_at IS NOT NULL AND project_tasks.completed_at <= project_tasks.due_date THEN 1 ELSE 0 END) as on_time"),
                    DB::raw("SUM(CASE WHEN project_tasks.due_date IS NOT NULL AND project_tasks.completed_at IS NOT NULL AND project_tasks.completed_at > project_tasks.due_date THEN 1 ELSE 0 END) as late")
                )
                ->where('project_tasks.status', 'completed');

            if ($projectId) {
                $tasksPerUserQuery->where('project_tasks.project_id', $projectId);
            }

            $tasksPerUser = $tasksPerUserQuery
                ->groupBy('users.id', 'users.name', 'users.photo')
                ->orderByDesc('total_completed')
                ->limit(10)
                ->get();

            // convert to resource (includes photo/url)
            $tasksPerUser = TaskPerUserResource::collection($tasksPerUser);

            // Projects progress summary (if project_id provided, only that project)
            $projectsQuery = Project::select('projects.id', 'projects.name')
                ->withCount([
                    'tasks as total_tasks' => function ($q) use ($projectId) {
                        if ($projectId) $q->where('project_id', $projectId);
                        $q->select(DB::raw('count(*)'));
                    },
                    'tasks as completed_tasks' => function ($q) use ($projectId) {
                        if ($projectId) $q->where('project_id', $projectId);
                        $q->where('status', 'completed')->select(DB::raw('count(*)'));
                    },
                    'tasks as overdue_tasks' => function ($q) use ($projectId) {
                        if ($projectId) $q->where('project_id', $projectId);
                        $q->whereIn('status', ['pending', 'in_progress'])
                          ->whereNotNull('due_date')
                          ->where('due_date', '<', Carbon::now())
                          ->select(DB::raw('count(*)'));
                    },
                ]);

            if ($projectId) {
                $projectsQuery->where('projects.id', $projectId);
            }

            $projects = $projectsQuery->get()
                ->map(function ($p) {
                    $total = $p->total_tasks ?: 0;
                    $completed = $p->completed_tasks ?: 0;
                    $percent = $total > 0 ? round(($completed / $total) * 100, 2) : 0;

                    $status = 'on_track';
                    if ($p->overdue_tasks > 0) {
                        $status = 'overdue';
                    } else {
                        $dueSoonCount = ProjectTask::where('project_id', $p->id)
                            ->whereIn('status', ['pending','in_progress'])
                            ->whereNotNull('due_date')
                            ->whereBetween('due_date', [Carbon::now(), Carbon::now()->addDays(7)])
                            ->count();

                        if ($percent < 80 && $dueSoonCount > 0) {
                            $status = 'at_risk';
                        }
                    }

                    return [
                        'id' => $p->id,
                        'name' => $p->name,
                        'total_tasks' => $total,
                        'completed_tasks' => $completed,
                        'overdue_tasks' => $p->overdue_tasks,
                        'percent_complete' => $percent,
                        'status' => $status,
                    ];
                });

            // Recent activity: latest task creations and completions (optionally filtered)
            $recentTasksQuery = ProjectTask::select('id','project_id','title','status','assigned_to','created_at','completed_at')
                ->orderByDesc('updated_at')
                ->limit(20);

            if ($projectId) {
                $recentTasksQuery->where('project_id', $projectId);
            }

            $recentTasks = $recentTasksQuery->get();

            return [
                'summary' => [
                    'total_projects' => $projectId ? Project::where('id', $projectId)->count() : Project::count(),
                    'total_tasks' => $totalTasks,
                    'completed_tasks' => $completedTasks,
                    'in_progress' => $inProgress,
                    'pending' => $pending,
                    'overdue_tasks' => $overdueTasks,
                    'avg_completion_hours' => $avgCompletionHours,
                    'on_time_rate' => ($completedOnTime + $completedLate) > 0 ? round( ($completedOnTime / ($completedOnTime + $completedLate)) * 100, 2) : null,
                ],
                'tasks_by_status' => $tasksByStatus,
                'tasks_per_user' => $tasksPerUser,
                'completed_on_time_vs_late' => [
                    'on_time' => $completedOnTime,
                    'late' => $completedLate,
                ],
                'projects' => $projects,
                'recent_tasks' => $recentTasks,
            ];
        });

        return $this->sendResponse($data, 'Dashboard data');
    }

    /**
     * Lightweight endpoint for chart: tasks per day (completed) last N days
     */
    public function tasksCompletedByDay(Request $request)
    {
        $days = (int)$request->get('days', 30);
        $projectId = $request->query('project_id');
        $start = Carbon::now()->subDays($days)->startOfDay();

        $q = ProjectTask::whereNotNull('completed_at')
            ->where('completed_at', '>=', $start);

        if ($projectId) $q->where('project_id', $projectId);

        $rows = $q->selectRaw('DATE(completed_at) as day, count(*) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        // fill gaps for charting
        $labels = [];
        $data = [];
        for ($i = 0; $i <= $days; $i++) {
            $d = $start->copy()->addDays($i)->toDateString();
            $labels[] = $d;
            $found = $rows->firstWhere('day', $d);
            $data[] = $found ? (int)$found->total : 0;
        }

        return $this->sendResponse(['labels' => $labels, 'data' => $data], 'Tasks completed per day');
    }

    /**
     * Lightweight endpoint: average completion hours per project (for chart)
     */
    public function avgCompletionByProject(Request $request)
    {
        $projectId = $request->query('project_id');

        $rows = ProjectTask::whereNotNull('completed_at')
            ->when($projectId, fn($q) => $q->where('project_id', $projectId))
            ->selectRaw('project_id, AVG(TIMESTAMPDIFF(SECOND, created_at, completed_at))/3600 as avg_hours')
            ->groupBy('project_id')
            ->get();

        $projects = Project::whereIn('id', $rows->pluck('project_id'))->get()->keyBy('id');

        $result = $rows->map(function ($r) use ($projects) {
            return [
                'project_id' => $r->project_id,
                'project_name' => $projects->has($r->project_id) ? $projects[$r->project_id]->name : "Project {$r->project_id}",
                'avg_hours' => round((float)$r->avg_hours, 2),
            ];
        });

        return $this->sendResponse($result, 'Average completion time per project');
    }
}